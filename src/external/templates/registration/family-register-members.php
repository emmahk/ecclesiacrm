<?php

use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Bootstrapper;

// Set the page title and include HTML header
$sPageTitle = _("Family Registration");
require(SystemURLs::getDocumentRoot() . "/Include/HeaderNotLoggedIn.php");
?>
<form action="<?= SystemURLs::getRootPath() ?>/external/register/confirm" method="post">
    <div class="register-box register-box-custom">
        <div class="register-logo">
            <a href="<?= SystemURLs::getRootPath() ?>/"><b>Ecclesia</b>CRM</a>
        </div>

        <div class="register-box-body">

            <div class="card card-primary">
                <div class="card-header with-border">
                    <h3
                        class="card-title"><?= _('Register') . ' <b>"' . $family->getName() . '"</b> ' . $familyCount . ' ' . _('Family Members') ?></h3>
                </div>
                <!-- /.box-header -->
                <div class="card-body">
                    <?php for ($x = 1;
                               $x <= $familyCount;
                               $x++) {
                        ?>
                        <div class="card card-secondary">
                            <div class="card-header with-border">
                                <h4 class="card-title">
                                    <?= _("Family Member") . " #" . $x ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group has-feedback">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <select name="memberRole-<?= $x ?>" class="form-control">
                                                <?php
                                                switch ($x) {
                                                    case 1:
                                                        $defaultRole = SystemConfig::getValue('sDirRoleHead');
                                                        break;
                                                    case 2:
                                                        $defaultRole = SystemConfig::getValue('sDirRoleSpouse');
                                                        break;
                                                    default:
                                                        $defaultRole = SystemConfig::getValue('sDirRoleChild');
                                                        break;
                                                }

                                                foreach ($familyRoles as $role) { ?>
                                                    <option
                                                        value="<?= $role->getOptionId() ?>" <?php if ($role->getOptionId() == $defaultRole) {
                                                        echo "selected";
                                                    } ?>><?= $role->getOptionName() ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <select name="memberGender-<?= $x ?>" class="form-control">
                                                <option value="1"><?= _('Male') ?></option>
                                                <option value="2"><?= _('Female') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group has-feedback">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <input name="memberFirstName-<?= $x ?>" class="form-control" maxlength="50"
                                                   placeholder="<?= _('First Name') ?>" required>
                                        </div>
                                        <div class="col-lg-6">
                                            <input name="memberLastName-<?= $x ?>" class="form-control"
                                                   value="<?= $family->getName() ?>" maxlength="50"
                                                   placeholder="<?= _('Last Name') ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group has-feedback">
                                    <div class="input-group">
                                        <input name="memberEmail-<?= $x ?>" class="form-control" maxlength="50"
                                               placeholder="<?= _('Email') ?>">
                                        <div class="input-group-append">
                                            <div class="input-group-text">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-envelope"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group has-feedback">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="input-group">
                                                <select name="memberPhoneType-<?= $x ?>" class="form-control">
                                                    <option value="mobile"><?= _('Mobile') ?></option>
                                                    <option value="home"><?= _('Home') ?></option>
                                                    <option value="work"><?= _('Work') ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <div class="input-group">
                                                <input name="memberPhone-<?= $x ?>" class="form-control" maxlength="30"
                                                       data-inputmask='"mask": "<?= SystemConfig::getValue('sPhoneFormat') ?>"'
                                                       data-mask
                                                       placeholder="<?= _('Phone') ?>">

                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-phone"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group has-feedback">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control inputDatePicker"
                                                       name="memberBirthday-<?= $x ?>">
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <div class="input-group-addon">
                                                            <i class="fa fa-birthday-cake"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <label>
                                                <input type="checkbox"
                                                       name="memberHideAge-<?= $x ?>">&nbsp; <?= _('Hide Age') ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    } ?>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><?= _('Next'); ?></button>
                </div>
            </div>
        </div>
    </div>

</form>
<?php
// Add the page footer
require(SystemURLs::getDocumentRoot() . "/Include/FooterNotLoggedIn.php");
?>

<script nonce="<?= SystemURLs::getCSPNonce() ?>">
    $(function () {
        $(".inputDatePicker").datepicker({
            autoclose: true,
            format: "<?= SystemConfig::getValue('sDatePickerPlaceHolder') ?>",
            language: "<?= Bootstrapper::GetCurrentLocale()->getLanguageCode() ?>"
        });
        $("[data-mask]").inputmask();
    });
</script>
