<?php
/*******************************************************************************
 *
 *  filename    : MenuBar.php
 *  website     : http://www.ecclesiacrm.com
 *  function    : List all Church Events
 *
 *  This code is under copyright not under MIT Licence
 *  copyright   : 2018 Philippe Logel all right reserved not MIT licence
 *                This code can't be incoprorated in another software without authorizaion
 *  Updated     : 2019/02/5
 *
 ******************************************************************************/

namespace EcclesiaCRM\MenuBar;

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\ListOptionQuery;
use EcclesiaCRM\GroupQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use EcclesiaCRM\DepositQuery;
use EcclesiaCRM\MenuLinkQuery;
use EcclesiaCRM\Service\MailChimpService;
use EcclesiaCRM\SessionUser;


class MenuBar extends Menu
{
    private $_title;
    private $_maxStr = 21;// maximum numbers of char in the menu items


    // Simple : Constructor
    public function __construct($title)
    {
        $this->_title = $title;
        $this->createMenuBar();
    }

    private function addHomeArea()
    {
        // home Area
        $menu = new Menu (_("Private Space"), "fa fa-home", "", true);

        $menuItem = new Menu (_("Home"), "fa fa-user", "PersonView.php?PersonID=" . SessionUser::getUser()->getPersonId(), true, $menu);
        $menuItem = new Menu (_("Change Password"), "fa fa-key", "UserPasswordChange.php", true, $menu);
        $menuItem = new Menu (_("Change Settings"), "fa fa-gear", "SettingsIndividual.php", true, $menu);
        $menuItem = new Menu (_("Documents"), "fa fa fa-files-o", "PersonView.php?PersonID=" . SessionUser::getUser()->getPersonId() . "&documents=true", true, $menu);

        if (SessionUser::getUser()->isEDrive()) {
            $menuItem = new Menu (_("EDrive"), "fa fa-cloud", "PersonView.php?PersonID=" . SessionUser::getUser()->getPersonId() . "&edrive=true", true, $menu);
        }

        if (SystemConfig::getBooleanValue("bEnabledMenuLinks")) {
            $this->addPersonMenuLinks($menu);
        }

        $this->addMenu($menu);
    }

    public function addGDPRMenu()
    {
            // the GDPR Menu
            $menu = new Menu (_("GDPR"), "fa fa-get-pocket pull-right&quot;", "", true);
            $menuItem = new Menu (_("Dashboard"), "fa fa-rebel", "v2/gdpr", true, $menu);
            $menuItem = new Menu (_("Data Structure"), "fa fa-user-secret", "v2/gdpr/gdprdatastructure", true, $menu);
            $menuItem = new Menu (_("View Inactive Persons"), "fa fa-circle-o", "v2/personlist/GDRP", true, $menu);
            $menuItem = new Menu (_("View Inactive Families"), "fa fa-circle-o", "v2/familylist/GDRP", true, $menu);

            $this->addMenu($menu);
    }

    public function addEventMenu()
    {
        // the Events Menu
        $menu = new Menu (_("Events") . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", "fa fa-ticket pull-right&quot;", "", true);
        // add the badges
        $menu->addBadge('badge badge-warning', 'EventsNumber', 0);
        $menu->addBadge('badge badge-danger', 'BirthdateNumber', 0);
        $menu->addBadge('badge badge-primary', 'AnniversaryNumber', 0);// badge à la place de label

        $menuItem = new Menu (_("Calendar"), "fa fa-calendar fa-calendar pull-left&quot;", "v2/calendar", true, $menu);

        if (SessionUser::getUser()->isShowMapEnabled()) {
            $menuItem = new Menu (_("View on Map"), "fa fa-map-o", "v2/map/-2", true, $menu);
        }

        $menuItem = new Menu (_("List Church Events"), "fa fa-circle-o", "ListEvents.php", true, $menu);
        $menuItem = new Menu (_("List Event Types"), "fa fa-circle-o", "EventNames.php", SessionUser::getUser()->isAdmin(), $menu);
        $menuItem = new Menu (_("Call the Register"), "fa fa-circle-o", "Checkin.php", true, $menu);

        $this->addMenu($menu);

    }

    private function addPeopleMenu()
    {
        // the People menu
        $menu = new Menu (_("People") . " & " . _("Families"), "fa fa-families", "#", true);

        $menuItem = new Menu (_("Dashboard"), "fa fa-circle-o", "v2/people/dashboard", SessionUser::getUser()->isAddRecordsEnabled(), $menu);

        $menuItem->addLink("GeoPage.php");
        $menuItem->addLink("UpdateAllLatLon.php");
        $menuItem->addLink("members/self-register.php");
        $menuItem->addLink("members/self-verify-updates.php");
        $menuItem->addLink("members/online-pending-verify.php");
        $menuItem->addLink("GroupReports.php");
        $menuItem->addLink("DirectoryReports.php");
        $menuItem->addLink("ReminderReport.php");
        $menuItem->addLink("LettersAndLabels.php");
        $menuItem->addLink("USISTAddressVerification.php");


        $menuItem = new Menu (_("Meta Search Engine"), "fa fa-search", "v2/people/list/person", true, $menu);
        if (SessionUser::getUser()->isShowMapEnabled()) {
            $menuItem = new Menu (_("View on Map"), "fa fa-map-o", "v2/map/-1", true, $menu);
        }

        $menuItem = new Menu (_("Directory reports"), "fa fa-circle-o", "DirectoryReports.php", true, $menu);

        if (SessionUser::getUser()->isEditRecordsEnabled()) {
            $menuItem = new Menu (_("Persons"), "fa fa-angle-double-right", "#", true, $menu);
            $menuItemItem = new Menu (_("Add New Person"), "fa fa-circle-o", "PersonEditor.php", SessionUser::getUser()->isAddRecordsEnabled(), $menuItem);
            $menuItemItem = new Menu (_("View Single Persons"), "fa fa-circle-o", "v2/familylist/single", true, $menuItem);
            $menuItemItem = new Menu (_("View Active Persons"), "fa fa-circle-o", "v2/personlist/", true, $menuItem);
            $menuItemItem->addLink("v2/personlist");
            $menuItemItem = new Menu (_("View Inactive Persons"), "fa fa-circle-o", "v2/personlist/inactive", true, $menuItem);

            $menuItem = new Menu (_("Families"), "fa fa-angle-double-right", "#", true, $menu);
            $menuItemItem = new Menu (_("Add New Family"), "fa fa-circle-o", "FamilyEditor.php", SessionUser::getUser()->isAddRecordsEnabled(), $menuItem);
            $menuItemItem = new Menu (_("View Active Families"), "fa fa-circle-o", "v2/familylist/", true, $menuItem);
            $menuItemItem->addLink("v2/familylist");
            $menuItemItem = new Menu (_("View Inactive Families"), "fa fa-circle-o", "v2/familylist/inactive", true, $menuItem);

            $menuItem = new Menu (_("Empty Addresses"), "fa fa-angle-double-right", "v2/familylist/empty", true, $menu);
        }

        $this->addMenu($menu);
    }

    private function addGroups()
    {
        // the assigned Groups
        $menu = new Menu (_("Groups"), "fa fa-group", "#", true);

        $menuItem = new Menu (_("List Groups"), "fa fa-circle-o", "v2/group/list", SessionUser::getUser()->isAddRecordsEnabled(), $menu);
        $menuItem->addLink("OptionManager.php?mode=grptypes");
        $menuItem->addLink("OptionManager.php?mode=grptypes&ListID=3");


        $listOptions = ListOptionQuery::Create()
            ->filterById(3) // the group category
            ->filterByOptionType('normal')
            ->orderByOptionSequence()
            ->find();

        foreach ($listOptions as $listOption) {
            $groups = GroupQuery::Create()
                ->useGroupTypeQuery()
                ->filterByListOptionId($listOption->getOptionId())
                ->endUse()
                ->filterByType(3)// normal groups
                ->orderByName()
                ->find();

            if ($groups->count() > 0) {// only if the groups exist : !empty doesn't work !

                $menuItem = new Menu ($listOption->getOptionName(), "fa fa-user-o", "#", true, $menu);

                foreach ($groups as $group) {
                    $str = $group->getName();
                    if (mb_strlen($str) > $this->_maxStr) {
                        $str = mb_substr($str, 0, $this->_maxStr - 3) . " …";
                    }

                    $menuItemItem = new Menu ($str, "fa fa-circle-o", "v2/group/" . $group->getID() . "/view", true, $menuItem);
                    $menuItemItem->addLink("GroupEditor.php?GroupID=" . $group->getID());
                    $menuItemItem->addLink("GroupPropsFormEditor.php?GroupID=" . $group->getID());
                    $menuItemItem->addLink("v2/group/" . $group->getID() . "/badge/1/normal");
                    $menuItemItem->addLink("v2/group/" . $group->getID() . "/badge/0/normal");

                    if (SessionUser::getUser()->isShowMapEnabled()) {
                        $menuItemItem->addLink("v2/map/" . $group->getID());
                    }
                }
            }
        }

        // now we're searching the unclassified groups
        $groups = GroupQuery::Create()
            ->useGroupTypeQuery()
            ->filterByListOptionId(0)
            ->endUse()
            ->filterByType(3) // normal groups
            ->orderByName()
            ->find();

        if ($groups->count() > 0) {// only if the groups exist : !empty doesn't work !
            $menuItem = new Menu (_("Unassigned"), "fa fa-user-o", "#", true, $menu);

            foreach ($groups as $group) {
                $menuItemItem = new Menu ($group->getName(), "fa fa-angle-double-right", "v2/group/" . $group->getID() . "/view", true, $menuItem);
                $menuItemItem->addLink("GroupEditor.php?GroupID=" . $group->getID());
                $menuItemItem->addLink("GroupPropsFormEditor.php?GroupID=" . $group->getID());
            }
        }

        $menuItem = new Menu (_("Group Assignment Helper"), "fa fa-circle-o", "v2/people/list/groupassign", true, $menu);

        $this->addMenu($menu);
    }

    private function addSundaySchoolGroups()
    {
        $menu = new Menu (_("Sunday School"), "fa fa-child", "#", true);

        $menuItem = new Menu (_("Dashboard"), "fa fa-circle-o", "v2/sundayschool/dashboard", true, $menu);
        $menuItem->addLink("v2/sundayschool/reports");
        $menuItem->addLink("OptionManager.php?mode=grptypesSundSchool");
        $menuItem->addLink("OptionManager.php?mode=grptypesSundSchool&ListID=3");


        $listOptions = ListOptionQuery::Create()
            ->filterById(3) // the group category
            ->filterByOptionType('sunday_school')
            ->orderByOptionSequence()
            ->find();

        foreach ($listOptions as $listOption) {
            $groups = GroupQuery::Create()
                ->useGroupTypeQuery()
                ->filterByListOptionId($listOption->getOptionId())
                ->endUse()
                ->filterByType(4)// sunday groups
                ->orderByName()
                ->find();

            if ($groups->count() > 0) {// only if the groups exist : !empty doesn't work !

                $menuItem = new Menu ($listOption->getOptionName(), "fa fa-user-o", "#", true, $menu);

                foreach ($groups as $group) {
                    $str = $group->getName();
                    if (mb_strlen($str) > $this->_maxStr) {
                        $str = mb_substr($str, 0, $this->_maxStr - 3) . " …";
                    }

                    $menuItemItem = new Menu ($str, "fa fa-circle-o", "v2/sundayschool/" . $group->getID() . "/view", true, $menuItem);
                    $menuItemItem->addLink("GroupEditor.php?GroupID=" . $group->getID());
                    $menuItemItem->addLink("GroupPropsFormEditor.php?GroupID=" . $group->getID());
                    $menuItemItem->addLink("v2/group/" . $group->getID() . "/badge/1/sundayschool");
                    $menuItemItem->addLink("v2/group/" . $group->getID() . "/badge/0/sundayschool");

                    if (SessionUser::getUser()->isShowMapEnabled()) {
                        $menuItemItem->addLink("v2/map/" . $group->getID());
                    }
                }
            }
        }

        // now we're searching the unclassified groups
        $groups = GroupQuery::Create()
            ->useGroupTypeQuery()
            ->filterByListOptionId(0)
            ->endUse()
            ->filterByType(4) // sunday group groups
            ->orderByName()
            ->find();

        if ($groups->count() > 0) {// only if the groups exist : !empty doesn't work !
            $menuItem = new Menu (_("Unassigned"), "fa fa-user-o", "#", true, $menu);

            foreach ($groups as $group) {
                $str = _($group->getName());
                if (mb_strlen($str) > $this->_maxStr) {
                    $str = mb_substr($str, 0, $this->_maxStr - 3) . " …";
                }

                $menuItemItem = new Menu ($str, "fa fa-angle-double-right", "v2/sundayschool/" . $group->getID() . "/view", true, $menuItem);
                $menuItemItem->addLink("GroupEditor.php?GroupID=" . $group->getID());
                $menuItemItem->addLink("v2/group/" . $group->getID() . "/view");
            }
        }

        $this->addMenu($menu);
    }

    private function addGlobalMenuLinks()
    {
        $menuLinks = MenuLinkQuery::Create()->orderByOrder(Criteria::ASC)->findByPersonId(null);

        if ($menuLinks->count()) {
            $menu = new Menu (_("Global Custom Menus"), "fa fa-link", "", true, null, "global_custom_menu");
            $menu->addLink("v2/menulinklist");

            foreach ($menuLinks as $menuLink) {
                $menuItem = new Menu ($menuLink->getName(), "fa fa-circle-o", $menuLink->getUri(), true, $menu);
            }
        } else {
            $menu = new Menu (_("Global Custom Menus"), "fa fa-link", "v2/menulinklist", true, null, "global_custom_menu");
        }


        $this->addMenu($menu);
    }

    private function addPersonMenuLinks($mainmenu)
    {
        $menuLinks = MenuLinkQuery::Create()->orderByOrder(Criteria::ASC)->findByPersonId(SessionUser::getUser()->getPersonId());

        $menuItem = new Menu (_("Custom Menus"), "fa fa-link", "#", true, $mainmenu, "personal_custom_menu_" . SessionUser::getUser()->getPersonId());
        $menuItem1 = new Menu (_("Dashboard"), "fa fa-circle-o", "v2/menulinklist/" . SessionUser::getUser()->getPersonId(), true, $menuItem);

        foreach ($menuLinks as $menuLink) {
            $menuItemItem1 = new Menu ($menuLink->getName(), "fa fa-angle-double-right", $menuLink->getUri(), true, $menuItem);
        }
    }

    private function addPastoralCare()
    {
        $menu = new Menu (_("Pastoral Care"), "fa fa-heartbeat", "#", true, null, "pastoralcare_menu");
        $menuItem1 = new Menu (_("Dashboard"), "fa fa-circle-o", "v2/pastoralcare/dashboard", true, $menu);
        $menuItem1 = new Menu (_("By Classifications"), "fa fa-circle-o", "v2/pastoralcare/membersList", true, $menu);

        $this->addMenu($menu);
    }

    private function addMeeting()
    {
        $menu = new Menu (_("Meetings"), "fa fa-video-camera", "v2/meeting/dashboard", true);

        $this->addMenu($menu);
    }

    private function addMailMenu()
    {
        // the Email
        $menu = new Menu (_("Email"), "fa fa-envelope", "#", true);

        $mailchimp = new MailChimpService();

        $menuMain = new Menu (_("MailChimp"), "fa fa-circle-o", "#", SessionUser::getUser()->isMailChimpEnabled(), $menu);

        $menuItem = new Menu (_("Dashboard"), "fa fa-circle-o", "v2/mailchimp/dashboard", SessionUser::getUser()->isMailChimpEnabled(), $menuMain, "lists_class_main_menu");
        $menuItem->addLink("v2/mailchimp/duplicateemails");
        $menuItem->addLink("v2/mailchimp/debug");
        $menuItem->addLink("v2/mailchimp/notinmailchimpemailspersons");
        $menuItem->addLink("v2/mailchimp/notinmailchimpemailsfamilies");


        $menuItemItem = new Menu (_("Email Lists"), "fa fa-circle-o", "#", true, $menuMain, "lists_class_menu " . (($mailchimp->isLoaded()) ? "" : "hidden"));

        if ($mailchimp->isLoaded()) {// to accelerate the Menu.php the first time
            $mcLists = $mailchimp->getLists();

            foreach ($mcLists as $list) {
                $menuItemItemItem = new Menu ($list['name']/*.' <small class="badge pull-right bg-blue current-deposit-item">'.$list['stats']['member_count'].'</small>'*/, "fa fa-circle-o", "v2/mailchimp/managelist/" . $list['id'], true, $menuItemItem, "listName" . $list['id']);

                $campaigns = $mailchimp->getCampaignsFromListId($list['id']);

                foreach ($campaigns as $campaign) {
                    //$menuItemItemItem = new Menu ($campaign['settings']['title'],"fa fa-circle-o","email/MailChimp/ManageList.php?list_id=".$list['id'],true,$menuItemItemItem);
                    $menuItemItemItem->addLink("v2/mailchimp/campaign/" . $campaign['id']);
                }
            }
        } else {// we add just a false item
            $menuItemItemItem = new Menu ("false item", "fa fa-circle-o", "#", true, $menuItemItem, "#");
        }

        $this->addMenu($menu);

    }

    private function addDepositMenu()
    {
        $menu = new Menu (_("Deposit") . "&nbsp;&nbsp;&nbsp;", "fa fa-bank", "#", SessionUser::getUser()->isFinanceEnabled());
        // add the badges
        $deposit = DepositQuery::Create()->findOneById($_SESSION['iCurrentDeposit']);
        $deposits = DepositQuery::Create()->find();

        $numberDeposit = 0;

        if (!empty($deposits)) {
            $numberDeposit = $deposits->count();
        }

        //echo '<small class="badge pull-right bg-green count-deposit">'.$numberDeposit. "</small>".((!empty($deposit))?('<small class="badge pull-right bg-blue current-deposit" data-id="'.$_SESSION['iCurrentDeposit'].'">'._("Current")." : ".$_SESSION['iCurrentDeposit'] . "</small>"):"")."\n";
        if (!empty($deposit)) {
            $menu->addBadge('badge badge-primary current-deposit', '', _("Current") . " : " . $_SESSION['iCurrentDeposit'], $_SESSION['iCurrentDeposit']);
        }
        $menu->addBadge('badge badge-success  count-deposit', '', $numberDeposit);


        $menuItem = new Menu (_("Envelope Manager"), "fa fa-circle-o", "ManageEnvelopes.php", SessionUser::getUser()->isFinanceEnabled(), $menu);
        $menuItem = new Menu (_("View All Deposits"), "fa fa-circle-o", "FindDepositSlip.php", SessionUser::getUser()->isFinanceEnabled(), $menu);
        $menuItem = new Menu (_("Electronic Payment Listing"), "fa fa-circle-o", "ElectronicPaymentList.php", SessionUser::getUser()->isFinanceEnabled(), $menu);
        $menuItem = new Menu (_("Deposit Reports"), "fa fa-circle-o", "FinancialReports.php", SessionUser::getUser()->isFinanceEnabled(), $menu);
        $menuItem = new Menu (_("Edit Deposit Slip") . '   : &nbsp;&nbsp;<small class="badge right badge-primary current-deposit-item"> #' . $_SESSION['iCurrentDeposit'] . '</small>', "fa fa-circle-o", "DepositSlipEditor.php?DepositSlipID=" . $_SESSION['iCurrentDeposit'], SessionUser::getUser()->isFinanceEnabled(), $menu, "deposit-current-deposit-item");

        $this->addMenu($menu);

    }

    private function addFundraiserMenu()
    {
        // the menu Fundraisers
        $menu = new Menu (_("Fundraiser"), "fa fa-money", "#", SessionUser::getUser()->isFinanceEnabled());

        $menuItem = new Menu (_("Create New Fundraiser"), "fa fa-circle-o", "FundRaiserEditor.php?FundRaiserID=-1", SessionUser::getUser()->isFinanceEnabled(), $menu);
        $menuItem = new Menu (_("View All Fundraisers"), "fa fa-circle-o", "v2/fundraiser/find", SessionUser::getUser()->isFinanceEnabled(), $menu);
        if (isset($_SESSION['iCurrentFundraiser'])) {
            $menuItem = new Menu (_("Edit Last Fundraiser") . '   : &nbsp;&nbsp;<small class="badge right badge-primary current-deposit-item"> #' . $_SESSION['iCurrentFundraiser'] . '</small>', "fa fa-circle-o", "FundRaiserEditor.php?FundRaiserID=" . $_SESSION['iCurrentFundraiser'], SessionUser::getUser()->isFinanceEnabled(), $menu, "deposit-current-deposit-item");
        }
        if (isset($_SESSION['iCurrentFundraiser'])) {
            $menuItem->addLink("v2/fundraiser/paddlenum/list/".$_SESSION['iCurrentFundraiser']);

        }

        if (isset($_SESSION['iCurrentFundraiser'])) {
            $menuItem->addLink('PaddleNumList.php?FundRaiserID='.$_SESSION['iCurrentFundraiser']);
        }

        $this->addMenu($menu);
    }

    private function createMenuBar()
    {

        $menuItem = new Menu (_("Dashboard"), "fa fa-dashboard", "menu", true);
        $menuItem->addLink("Menu.php");

        $this->addMenu($menuItem);

        $this->addHomeArea();
        if (SessionUser::getUser()->isGdrpDpoEnabled() && SystemConfig::getValue('bGDPR')) {
            $this->addGDPRMenu();
        }

        if (SystemConfig::getBooleanValue("bEnabledEvents")) {
            $this->addEventMenu();
        }

        $this->addPeopleMenu();
        $this->addGroups();

        // we add the sundayschool groups
        if (SystemConfig::getBooleanValue("bEnabledSundaySchool")) {
            $this->addSundaySchoolGroups();
        }

        $this->addMeeting();

        if (SessionUser::getUser()->isPastoralCareEnabled()) {
            $this->addPastoralCare();
        }

        if (SessionUser::getUser()->isMailChimpEnabled()) {
            $this->addMailMenu();
        }

        // The deposit
        if (SystemConfig::getBooleanValue("bEnabledFinance") && SessionUser::getUser()->isFinanceEnabled()) {
            $this->addDepositMenu();
        }

        if (SystemConfig::getBooleanValue("bEnabledFundraiser")) {
            $this->addFundraiserMenu();
        }

        // the menu report
        $menu = new Menu (_("Data/Reports"), "fa fa-file-pdf-o", "#", SessionUser::getUser()->isShowMenuQueryEnabled());

        $menuItem = new Menu (_("Reports Menu"), "fa fa-circle-o", "ReportList.php", SessionUser::getUser()->isFinanceEnabled() && SystemConfig::getBooleanValue('bEnabledFinance') || SystemConfig::getBooleanValue('bEnabledSundaySchool'), $menu);
        $menuItem = new Menu (_("Query Menu"), "fa fa-circle-o", "QueryList.php", SessionUser::getUser()->isShowMenuQueryEnabled(), $menu);

        if (SessionUser::getUser()->isShowMenuQueryEnabled()) {
            $this->addMenu($menu);
        }


        if (SystemConfig::getBooleanValue("bEnabledMenuLinks")) {
            $this->addGlobalMenuLinks();
        }
    }
}
