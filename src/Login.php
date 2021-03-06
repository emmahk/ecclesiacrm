<?php
/*******************************************************************************
 *
 *  filename    : Login.php
 *  website     : http://www.ecclesiacrm.com
 *  description : page header used for most pages
 *
 *  Copyright 2017 Philippe Logel
 *
 ******************************************************************************/

// Include the function library
require 'Include/Config.php';

$bSuppressSessionTests = true; // DO NOT MOVE
require 'Include/Functions.php';

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Service\SystemService;
use EcclesiaCRM\UserQuery;
use EcclesiaCRM\Emails\LockedEmail;
use EcclesiaCRM\Service\NotificationService;
use EcclesiaCRM\dto\ChurchMetaData;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\Utils\MiscUtils;
use EcclesiaCRM\PersonQuery;
use EcclesiaCRM\TokenQuery;
use EcclesiaCRM\Token;
use EcclesiaCRM\utils\RedirectUtils;
use EcclesiaCRM\Bootstrapper;


if (!Bootstrapper::isDBCurrent()) {
    RedirectUtils::Redirect('SystemDBUpdate.php');
    exit;
}

// Get the UserID out of user name submitted in form results
if (isset($_POST['User'])) {
    // Get the information for the selected user
    $UserName = InputUtils::LegacyFilterInput($_POST['User'], 'string', 32);
    $currentUser = UserQuery::create()->findOneByUserName($UserName);
    if ($currentUser == null) {
        // Set the error text
        $sErrorText = _('Invalid login or password');
    } // Block the login if a maximum login failure count has been reached
    elseif ($currentUser->isLocked()) {
        $sErrorText = _('Too many failed logins: your account has been locked.  Please contact an administrator.');
    } // test if the account has been deactivated
    elseif ($currentUser->getIsDeactivated()) {
        $sErrorText = _('This account has been deactiveted by an administrator.');
    } // Does the password match?
    elseif (!$currentUser->isPasswordValid($_POST['Password'])) {
        // Increment the FailedLogins
        $currentUser->setFailedLogins($currentUser->getFailedLogins() + 1);
        $currentUser->save();
        if (!empty($currentUser->getEmail()) && $currentUser->isLocked()) {
            $lockedEmail = new LockedEmail($currentUser);
            $lockedEmail->send();
        }

        // Set the error text
        $sErrorText = _('Invalid login or password');
    } else {
        // manage the token for the secret JWT UUID
        $token = TokenQuery::Create()->findOneByType("secret");

        if (is_null($token)) {
            $token = new Token ();
            $token->buildSecret();
            $token->save();
        }

        $dateNow = new DateTime("now");

        if ($dateNow > $token->getValidUntilDate()) {// the token expire
            // we delete the old token
            $token->delete();
            // we create a new one
            $token = new Token ();
            $token->buildSecret();
            $token->save();
        }

        // Set the LastLogin and Increment the LoginCount
        $date = new DateTime('now', new DateTimeZone(SystemConfig::getValue('sTimeZone')));
        $currentUser->setLastLogin($date->format('Y-m-d H:i:s'));
        $currentUser->setLoginCount($currentUser->getLoginCount() + 1);
        $currentUser->setFailedLogins(0);
        $currentUser->save();

        $_SESSION['user'] = $currentUser;

        // Set the UserID
        $_SESSION['iUserID'] = $currentUser->getPersonId();

        // Set the User's family id in case EditSelf is enabled
        $_SESSION['iFamID'] = $currentUser->getPerson()->getFamId();

        // for webDav we've to create the Home directory
        $currentUser->createHomeDir();

        // If user has administrator privilege, override other settings and enable all permissions.
        // this is usefull for : MiscUtils::requireUserGroupMembership in Include/Functions.php

        $_SESSION['bAdmin'] = $currentUser->isAdmin();                       //ok
        $_SESSION['bPastoralCare'] = $currentUser->isPastoralCareEnabled();         //ok
        $_SESSION['bMailChimp'] = $currentUser->isMailChimpEnabled();            //ok
        $_SESSION['bGdrpDpo'] = $currentUser->isGdrpDpoEnabled();              //ok
        $_SESSION['bMainDashboard'] = $currentUser->isMainDashboardEnabled();        //ok
        $_SESSION['bSeePrivacyData'] = $currentUser->isSeePrivacyDataEnabled();       //ok
        $_SESSION['bAddRecords'] = $currentUser->isAddRecordsEnabled();           //ok
        $_SESSION['bEditRecords'] = $currentUser->isEditRecordsEnabled();          //ok
        $_SESSION['bDeleteRecords'] = $currentUser->isDeleteRecordsEnabled();        //ok
        $_SESSION['bMenuOptions'] = $currentUser->isMenuOptionsEnabled();          //ok
        $_SESSION['bManageGroups'] = $currentUser->isManageGroupsEnabled();         //usefull in GroupView and in Properties
        $_SESSION['bFinance'] = $currentUser->isFinanceEnabled();              //ok
        $_SESSION['bNotes'] = $currentUser->isNotesEnabled();                //ok
        $_SESSION['bCanvasser'] = $currentUser->isCanvasserEnabled();            //ok
        $_SESSION['bEditSelf'] = $currentUser->isEditSelfEnabled();             //ok
        $_SESSION['bShowCart'] = $currentUser->isShowCartEnabled();             //ok
        $_SESSION['bShowMap'] = $currentUser->isShowMapEnabled();              //ok
        $_SESSION['bEDrive'] = $currentUser->isEDriveEnabled();               //ok
        $_SESSION['bShowMenuQuery'] = $currentUser->isShowMenuQueryEnabled();        //ok


        // Create the Cart
        $_SESSION['aPeopleCart'] = [];

        // Create the variable for the Global Message
        $_SESSION['sGlobalMessage'] = '';

        // Initialize the last operation time
        $_SESSION['tLastOperation'] = time();

        $_SESSION['bHasMagicQuotes'] = 0;

        // Pledge and payment preferences
        $_SESSION['sshowPledges'] = $currentUser->getShowPledges();
        $_SESSION['sshowPayments'] = $currentUser->getShowPayments();

        if (is_null($_SESSION['user']->getShowSince())) {
            $_SESSION['user']->setShowSince(date("Y-m-d", strtotime('-1 year')));
            $currentUser->save();
        }

        if (is_null($_SESSION['user']->getShowTo())) {
            $_SESSION['user']->setShowTo(date('Y-m-d'));
            $currentUser->save();
        }

        $_SESSION['idefaultFY'] = MiscUtils::CurrentFY(); // Improve the chance of getting the correct fiscal year assigned to new transactions
        $_SESSION['iCurrentDeposit'] = $currentUser->getCurrentDeposit();

        $systemService = new SystemService();
        $_SESSION['latestVersion'] = $systemService->getLatestRelese();
        NotificationService::updateNotifications();

        $_SESSION['isUpdateRequired'] = NotificationService::isUpdateRequired();

        $_SESSION['isSoftwareUpdateTestPassed'] = false;
        RedirectUtils::Redirect('Menu.php');
        exit;
    }
} elseif (isset($_GET['username'])) {
    $urlUserName = $_GET['username'];
}

$id = 0;
$type = "";

// we hold down the last id
if (isset($_SESSION['iUserID'])) {
    $id = $_SESSION['iUserID'];
}

// we hold down the last type of login : lock or nothing
if (isset($_SESSION['iLoginType'])) {
    $type = $_SESSION['iLoginType'];
}


if (isset($_GET['session']) && $_GET['session'] == "Lock") {// We are in a Lock session
    $type = $_SESSION['iLoginType'] = "Lock";
}

if (empty($urlUserName)) {
    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
        $urlUserName = $user->getUserName();
    } elseif (isset($_SESSION['username'])) {
        $urlUserName = $_SESSION['username'];
    }
}

// we destroy the session
session_destroy();

// we reopen a new one
session_start();

// we restore only this part
$_SESSION['iLoginType'] = $type;
$_SESSION['username'] = $urlUserName;
$_SESSION['iUserID'] = $id;

if ($type == "Lock" && $id > 0) {// this point is important for the photo in a lock session
    $person = PersonQuery::Create()
        ->findOneByID($_SESSION['iUserID']);
} else {
    $type = $_SESSION['iLoginType'] = "";
}

// Set the page title and include HTML header
$sPageTitle = _('Login');
require 'Include/HeaderNotLoggedIn.php';

?>
<div class="login-box" id="Login" <?= ($_SESSION['iLoginType'] != "Lock") ? "" : 'style="display: none;"' ?>>
    <div class="login-logo">
        Ecclesia<b>CRM</b><?= SystemService::getDBMainVersion() ?>
    </div>

    <!-- /.login-logo -->
    <div class="card login-box-body">
        <div class="card-body login-card-body">

            <p class="login-box-msg">
                <b><?= ChurchMetaData::getChurchName() ?></b><br/>
                <?= _('Please Login') ?>
            </p>

            <?php
            if (isset($_GET['Timeout'])) {
                $loginPageMsg = _('Your previous session timed out.  Please login again.');
            }

            // output warning and error messages
            if (isset($sErrorText)) {
                echo '<div class="alert alert-error">' . $sErrorText . '</div>';
            }
            if (isset($loginPageMsg)) {
                echo '<div class="alert alert-warning">' . $loginPageMsg . '</div>';
            }
            ?>

            <form class="form-signin" role="form" method="post" name="LoginForm" action="Login.php">
                <div class="form-group has-feedback">
                    <input type="text" id="UserBox" name="User" class="form-control" value="<?= $urlUserName ?>"
                           placeholder="<?= _('Email/Username') ?>" required autofocus>
                </div>
                <div class="form-group has-feedback">
                    <input type="password" id="PasswordBox" name="Password" class="form-control" data-toggle="password"
                           placeholder="<?= _('Password') ?>" required autofocus>
                    <br/>
                </div>
                <div class="row  mb-3">
                    <!-- /.col -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block"><i
                                class="fa fa-sign-in"></i> <?= _('Login') ?></button>
                    </div>
                </div>
                <p class="mb-1">
                    <?php if (SystemConfig::getBooleanValue('bEnableLostPassword')) {
                        ?>
                        <span class="text-right"><a
                                href="external/password/"><?= _("I forgot my password") ?></a></span>
                        <?php
                    } ?>
                </p>
            </form>

            <?php if (SystemConfig::getBooleanValue('bEnableSelfRegistration')) {
                ?>
                <div class="row  mb-3">
                    <!-- /.col -->
                    <div class="col-12">
                        <a href="<?= SystemURLs::getRootPath() ?>/external/register/" class="btn btn-primary btn-block bg-olive"><i
                                class="fa fa-user-plus"></i> <?= _('Register a new Family'); ?></a><br>
                    </div>
                </div>
                <?php
            } ?>
            <!--<a href="external/family/verify" class="text-center">Verify Family Info</a> -->

        </div>
    </div>
    <!-- /.login-box-body -->
</div>
<!-- /.login-box -->
<div class="lockscreen-wrapper" id="Lock" <?= ($_SESSION['iLoginType'] == "Lock") ? "" : 'style="display: none;"' ?>>
    <div class="login-logo">
        Ecclesia<b>CRM</b><?= SystemService::getDBMainVersion() ?>
    </div>

    <p class="login-box-msg">
        <b><?= ChurchMetaData::getChurchName() ?></b><br/>
        <?= _('Please Login') ?>
    </p>


    <div>
        <?php
        if (isset($_GET['Timeout'])) {
            $loginPageMsg = _('Your previous session timed out.  Please login again.');
        }

        // output warning and error messages
        if (isset($sErrorText)) {
            echo '<div class="alert alert-error">' . $sErrorText . '</div>';
        }
        if (isset($loginPageMsg)) {
            echo '<div class="alert alert-warning">' . $loginPageMsg . '</div>';
        }
        ?>
    </div>

    <div class="lockscreen-name text-center"><?= $urlUserName ?></div>

    <div class="lockscreen-item">

        <!-- lockscreen image -->
        <div class="lockscreen-image">
            <?php if ($_SESSION['iLoginType'] == "Lock") {
                ?>
                <img src="<?= str_replace(SystemURLs::getDocumentRoot(), "", $person->getPhoto()->getThumbnailURI()) ?>"
                     alt="User Image">
                <?php
            } ?>
        </div>
        <!-- /.lockscreen-image -->

        <!-- lockscreen credentials (contains the form) -->
        <form class="lockscreen-credentials" role="form" method="post" name="LoginForm" action="Login.php">
            <div class="input-group">
                <input type="hidden" id="UserBox" name="User" class="form-control" value="<?= $urlUserName ?>">

                <input type="password" id="PasswordBox" name="Password" class="form-control"
                       placeholder="<?= _('Password') ?>">

                <div class="input-group-append"><button type="submit" class="btn btn-default"><i class="fa fa-arrow-right text-muted"></i></button></div>
            </div>
        </form>
        <!-- /.lockscreen credentials -->
    </div>
    <!-- /.lockscreen-item -->
    <div class="help-block text-center">
        <?= _("Enter your password to retrieve your session") ?>
    </div>
    <div class="text-center">
        <a href="#" id="Login-div-appear"><?= _("Or sign in as a different user") ?></a>
    </div>
    <!-- /.login-box-body -->
</div>
<!-- /.lockscreen-wrapper -->
<script
    src="<?= SystemURLs::getRootPath() ?>/skin/external/bootstrap-show-password/bootstrap-show-password.min.js"></script>
<script nonce="<?= SystemURLs::getCSPNonce() ?>">
    <?php
    if ($_SESSION['iLoginType'] == "Lock") {
    ?>
    $('.login-page').addClass('lockscreen').removeClass('login-page');

    $(document).ready(function () {
        $("#Login").hide();
        document.title = 'Lock';
    });

    $("#Login-div-appear").click(function () {
        // 200 is the interval in milliseconds for the fade-in/out, we use jQuery's callback feature to fade
        // in the new div once the first one has faded out

        $("#Lock").fadeOut(100, function () {
            $("#Login").fadeIn(300);
            document.title = 'Login';
            $('.lockscreen').addClass('login-page').removeClass('lockscreen');
        });
    });
    <?php
    } else {
    ?>
    $('.hold-transition').addClass('login-page').removeClass('lockscreen');
    $(document).ready(function () {
        $("#Lock").hide();
        document.title = 'Login';
    });
    <?php
    }
    ?>
    var $buoop = {vs: {i: 13, f: -2, o: -2, s: 9, c: -2}, unsecure: true, api: 4};

    function $buo_f() {
        var e = document.createElement("script");
        e.src = "//browser-update.org/update.min.js";
        document.body.appendChild(e);
    }

    try {
        document.addEventListener("DOMContentLoaded", $buo_f, false)
    } catch (e) {
        window.attachEvent("onload", $buo_f)
    }

    $('#password').password('toggle');
    $("#password").password({
        eyeOpenClass: 'glyphicon-eye-open',
        eyeCloseClass: 'glyphicon-eye-close'
    });
</script>

<?php require 'Include/FooterNotLoggedIn.php'; ?>
