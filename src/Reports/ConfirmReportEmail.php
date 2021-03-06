<?php

/*******************************************************************************
 *
 *  filename    : Reports/ConfirmReportEmail.php
 *  last change : 2020-10-09 Philippe Logel
 *  description : Creates a email with all the confirmation letters asking member
 *                families to verify the information in the database.
 *
 *
 ******************************************************************************/

require '../Include/Config.php';
require '../Include/Functions.php';

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Reports\ChurchInfoReport;
use EcclesiaCRM\Emails\FamilyVerificationEmail;
use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\Utils\OutputUtils;
use EcclesiaCRM\Utils\RedirectUtils;
use EcclesiaCRM\Utils\LoggerUtils;

use EcclesiaCRM\PersonCustomMasterQuery;
use EcclesiaCRM\PersonCustomQuery;
use EcclesiaCRM\FamilyQuery;
use EcclesiaCRM\PersonQuery;
use EcclesiaCRM\GroupQuery;

use EcclesiaCRM\Map\GroupTableMap;
use EcclesiaCRM\Map\ListOptionTableMap;
use EcclesiaCRM\Map\PersonTableMap;

use Propel\Runtime\ActiveQuery\Criteria;

class EmailPDF_ConfirmReport extends ChurchInfoReport
{
    // Constructor
    public function __construct()
    {
        parent::__construct('P', 'mm', $this->paperFormat);
        $this->leftX = 10;
        $this->SetFont('Times', '', 10);
        $this->SetMargins(10, 20);

        $this->SetAutoPageBreak(false);
    }

    public function StartNewPage($fam_ID, $fam_Name, $fam_Address1, $fam_Address2, $fam_City, $fam_State, $fam_Zip, $fam_Country)
    {
        $curY = $this->StartLetterPage($fam_ID, $fam_Name, $fam_Address1, $fam_Address2, $fam_City, $fam_State, $fam_Zip, $fam_Country);
        $curY += 2 * SystemConfig::getValue('incrementY');
        $blurb = SystemConfig::getValue('sConfirm1');
        $this->WriteAt(SystemConfig::getValue('leftX'), $curY, $blurb);
        $curY += 2 * SystemConfig::getValue('incrementY');

        return $curY;
    }

    public function FinishPage($curY)
    {
        $curY += 2 * SystemConfig::getValue('incrementY');
        $this->WriteAt(SystemConfig::getValue('leftX'), $curY, SystemConfig::getValue('sConfirm2'));

        $curY += 3 * SystemConfig::getValue('incrementY');
        $this->WriteAt(SystemConfig::getValue('leftX'), $curY, SystemConfig::getValue('sConfirm3'));
        $curY += 2 * SystemConfig::getValue('incrementY');
        $this->WriteAt(SystemConfig::getValue('leftX'), $curY, SystemConfig::getValue('sConfirm4'));

        if (SystemConfig::getValue('sConfirm5') != '') {
            $curY += 2 * SystemConfig::getValue('incrementY');
            $this->WriteAt(SystemConfig::getValue('leftX'), $curY, SystemConfig::getValue('sConfirm5'));
            $curY += 2 * SystemConfig::getValue('incrementY');
        }
        if (SystemConfig::getValue('sConfirm6') != '') {
            $curY += 2 * SystemConfig::getValue('incrementY');
            $this->WriteAt(SystemConfig::getValue('leftX'), $curY, SystemConfig::getValue('sConfirm6'));
        }

        $curY += 4 * SystemConfig::getValue('incrementY');

        $this->WriteAt(SystemConfig::getValue('leftX'), $curY, SystemConfig::getValue('sConfirmSincerely') . ",");
        $curY += 4 * SystemConfig::getValue('incrementY');
        $this->WriteAt(SystemConfig::getValue('leftX'), $curY, SystemConfig::getValue('sConfirmSigner'));
    }
}

$familiesEmailed = 0;

// Get the list of custom person fields
$ormCustomFields = PersonCustomMasterQuery::create()
    ->orderByCustomOrder()
    ->find();

$numCustomFields = $ormCustomFields->count();

if ($numCustomFields > 0) {
    $iFieldNum = 0;
    foreach ($ormCustomFields as $ormCustomField) {
        $sCustomFieldName[$iFieldNum] = $ormCustomField->getCustomName();
        $iFieldNum += 1;
    }
}

$sSubQuery = '';
if (InputUtils::LegacyFilterInput($_GET['familyId'], 'int')) {
    $sSubQuery = ' and fam_id in (' . $_GET['familyId'] . ') ';
}

// Get all the families
$ormFamilies = FamilyQuery::create()
    ->usePersonQuery()
    ->filterByEmail('', \Propel\Runtime\ActiveQuery\Criteria::NOT_EQUAL)
    ->endUse()
    ->groupById()
    ->orderByName();

if (InputUtils::LegacyFilterInput($_GET['familyId'], 'int')) {
    $fams = explode(",", $_GET['familyId']);
    $ormFamilies->filterById($fams);
}

$ormFamilies->find();

$dataCol = 55;
$dataWid = 65;

// Loop through families
foreach ($ormFamilies as $fam) {
    // Instantiate the directory class and build the report.
    $pdf = new EmailPDF_ConfirmReport();

    $emaillist = [];

    $curY = $pdf->StartNewPage($fam->getId(), $fam->getName(), $fam->getAddress1(), $fam->getAddress2(), $fam->getCity(), $fam->getState(), $fam->getZip(), $fam->getCountry());
    $curY += SystemConfig::getValue('incrementY');

    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('Family Name'));
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, $fam->getName());
    $curY += SystemConfig::getValue('incrementY');
    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('Address') . ' 1');
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, $fam->getAddress1());
    $curY += SystemConfig::getValue('incrementY');
    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('Address') . ' 2');
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, $fam->getAddress2());
    $curY += SystemConfig::getValue('incrementY');
    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('City, State, Zip'));
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, ($fam->getCity() . ', ' . $fam->getState() . '  ' . $fam->getZip()));
    $curY += SystemConfig::getValue('incrementY');
    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('Home Phone'));
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, $fam->getHomePhone());
    $curY += SystemConfig::getValue('incrementY');
    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('Send Newsletter'));
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, $fam->getSendNewsLetter());
    $curY += SystemConfig::getValue('incrementY');

    // Missing the following information from the Family record:
    // Wedding date (if present) - need to figure how to do this with sensitivity
    // Family e-mail address

    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('Anniversary Date'));
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, OutputUtils::FormatDate($fam->getWeddingDate()->format('Y-m-d')));
    $curY += SystemConfig::getValue('incrementY');

    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell(SystemConfig::getValue('leftX'), $curY, $dataCol - SystemConfig::getValue('leftX'), _('Family Email'));
    $pdf->SetFont('Times', '', 10);
    $pdf->WriteAtCell($dataCol, $curY, $dataWid, $fam->getEmail());
    if (!empty($fam_Email)) {
        array_push($emaillist, $fam_Email);
    }

    $curY += SystemConfig::getValue('incrementY');
    $curY += SystemConfig::getValue('incrementY');


    $ormFamilyMembers = PersonQuery::create()
        ->addAlias('cls', ListOptionTableMap::TABLE_NAME)
        ->addMultipleJoin(array(
                array(PersonTableMap::COL_PER_CLS_ID, ListOptionTableMap::Alias("cls", ListOptionTableMap::COL_LST_OPTIONID)),
                array(ListOptionTableMap::Alias("cls", ListOptionTableMap::COL_LST_ID), 1)
            )
            , Criteria::LEFT_JOIN)
        ->addAsColumn('ClassName', ListOptionTableMap::alias('cls', ListOptionTableMap::COL_LST_OPTIONNAME))
        ->addAlias('fmr', ListOptionTableMap::TABLE_NAME)
        ->addMultipleJoin(array(
                array(PersonTableMap::COL_PER_FMR_ID, ListOptionTableMap::alias('fmr', ListOptionTableMap::COL_LST_OPTIONID)),
                array(ListOptionTableMap::Alias("fmr", ListOptionTableMap::COL_LST_ID), 2)
            )
            , Criteria::LEFT_JOIN)
        ->addAsColumn('FamRole', ListOptionTableMap::alias('fmr', ListOptionTableMap::COL_LST_OPTIONNAME))
        ->filterByFamId($fam->getId())
        ->orderByFmrId()
        ->find();

    $XName = 10;
    $XGender = 50;
    $XRole = 60;
    $XEmail = 90;
    $XBirthday = 135;
    $XCellPhone = 155;
    $XClassification = 180;
    $XWorkPhone = 155;
    $XRight = 208;

    $pdf->SetFont('Times', 'B', 10);
    $pdf->WriteAtCell($XName, $curY, $XGender - $XName, _('Member Name'));
    $pdf->WriteAtCell($XGender, $curY, $XRole - $XGender, _('M/F'));
    $pdf->WriteAtCell($XRole, $curY, $XEmail - $XRole, _('Adult/Child'));
    $pdf->WriteAtCell($XEmail, $curY, $XBirthday - $XEmail, _('Email'));
    $pdf->WriteAtCell($XBirthday, $curY, $XCellPhone - $XBirthday, _('Birthday'));
    $pdf->WriteAtCell($XCellPhone, $curY, $XClassification - $XCellPhone, _('Cell Phone'));
    $pdf->WriteAtCell($XClassification, $curY, $XRight - $XClassification, _('Member/Friend'));
    $pdf->SetFont('Times', '', 10);
    $curY += SystemConfig::getValue('incrementY');

    $numFamilyMembers = 0;

    foreach ($ormFamilyMembers as $aMember) {
        $numFamilyMembers++; // add one to the people count
        // Make sure the person data will display with adequate room for the trailer and group information
        if (($curY + $numCustomFields * SystemConfig::getValue('incrementY')) > 260) {
            $curY = $pdf->StartLetterPage($fam->getID(), $fam->getName(), $fam->getAddress1(), $fam->getAddress2(), $fam->getCity(), $fam->getState(), $fam->getZip(), $fam->getCountry());
            $pdf->SetFont('Times', 'B', 10);
            $pdf->WriteAtCell($XName, $curY, $XGender - $XName, _('Member Name'));
            $pdf->WriteAtCell($XGender, $curY, $XRole - $XGender, _('M/F'));
            $pdf->WriteAtCell($XRole, $curY, $XEmail - $XRole, _('Adult/Child'));
            $pdf->WriteAtCell($XEmail, $curY, $XBirthday - $XEmail, _('Email'));
            $pdf->WriteAtCell($XBirthday, $curY, $XCellPhone - $XBirthday, _('Birthday'));
            $pdf->WriteAtCell($XCellPhone, $curY, $XClassification - $XCellPhone, _('Cell Phone'));
            $pdf->WriteAtCell($XClassification, $curY, $XRight - $XClassification, _('Member/Friend'));
            $pdf->SetFont('Times', '', 10);
            $curY += SystemConfig::getValue('incrementY');
        }
        $iPersonID = $aMember->getId();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->WriteAtCell($XName, $curY, $XGender - $XName, $aMember->getFirstName() . ' ' . $aMember->getMiddleName() . ' ' . $aMember->getLastName());
        $pdf->SetFont('Times', '', 10);
        $genderStr = ($aMember->getGender() == 1 ? 'M' : 'F');
        $pdf->WriteAtCell($XGender, $curY, $XRole - $XGender, $genderStr);
        $pdf->WriteAtCell($XRole, $curY, $XEmail - $XRole, $aMember->getFamRole());
        $pdf->WriteAtCell($XEmail, $curY, $XBirthday - $XEmail, $aMember->getEmail());
        if (!empty($aMember->getEmail())) {
            array_push($emaillist, $aMember->getEmail());
        }
        if ($aMember->getBirthYear()) {
            $birthdayStr = $aMember->getBirthMonth() . '/' . $aMember->getBirthDay() . '/' . $aMember->getBirthYear();
        } else {
            $birthdayStr = '';
        }
        $pdf->WriteAtCell($XBirthday, $curY, $XCellPhone - $XBirthday, $birthdayStr);
        $pdf->WriteAtCell($XCellPhone, $curY, $XClassification - $XCellPhone, $aMember->getCellPhone());
        $pdf->WriteAtCell($XClassification, $curY, $XRight - $XClassification, $aMember->getClassName());
        $curY += SystemConfig::getValue('incrementY');
        // Missing the following information for the personal record: ??? Is this the place to put this data ???
        // Work Phone
        $pdf->WriteAtCell($XWorkPhone, $curY, $XRight - $XWorkPhone, _('Work Phone') . ':' . $aMember->getWorkPhone());
        $curY += SystemConfig::getValue('incrementY');
        $curY += SystemConfig::getValue('incrementY');

        // *** All custom fields ***
        // Get the list of custom person fields


        $xSize = 40;
        if ($numCustomFields > 0) {

            $rawQry = PersonCustomQuery::create();
            foreach ($ormCustomFields as $customField) {
                $rawQry->withColumn($customField->getCustomField());
            }

            if (!is_null($rawQry->findOneByPerId($aMember->getId()))) {
                $aCustomData = $rawQry->findOneByPerId($aMember->getId())->toArray();
            }

            $xInc = $XName; // Set the starting column for Custom fields
            // Here is where we determine if space is available on the current page to
            // display the custom data and still get the ending on the page
            // Calculations (without groups) show 84 mm is needed.
            // For the Letter size of 279 mm, this says that curY can be no bigger than 195 mm.
            // Leaving 12 mm for a bottom margin yields 183 mm.
            $numWide = 0; // starting value for columns

            foreach ($ormCustomFields as $customField) {
                if ($sCustomFieldName[$customField->getCustomOrder() - 1]) {
                    $currentFieldData = trim($aCustomData[$customField->getCustomField()]);

                    $currentFieldData = OutputUtils::displayCustomField($customField->getTypeId(), trim($aCustomData[$customField->getCustomField()]), $customField->getCustomSpecial(), false);

                    $OutStr = $sCustomFieldName[$customField->getCustomOrder() - 1] . ' : ' . $currentFieldData . '    ';
                    $pdf->WriteAtCell($xInc, $curY, $xSize, $sCustomFieldName[$customField->getCustomOrder() - 1]);
                    if ($currentFieldData == '') {
                        $pdf->SetFont('Times', 'B', 6);
                        $pdf->WriteAtCell($xInc + $xSize, $curY, $xSize, '');
                        $pdf->SetFont('Times', '', 10);
                    } else {
                        $pdf->WriteAtCell($xInc + $xSize, $curY, $xSize, $currentFieldData);
                    }
                    $numWide += 1; // increment the number of columns done
                    $xInc += (2 * $xSize); // Increment the X position by about 1/2 page width
                    if (($numWide % 2) == 0) { // 2 columns
                        $xInc = $XName; // Reset margin
                        $curY += SystemConfig::getValue('incrementY');
                    }
                }
            }

            //$pdf->WriteAt($XName,$curY,$OutStr);
            //$curY += (2 * SystemConfig::getValue("incrementY"));
        }
        $curY += 2 * SystemConfig::getValue('incrementY');
    }

    $curY += SystemConfig::getValue('incrementY');

    if (($curY + 2 * $numFamilyMembers * SystemConfig::getValue('incrementY')) >= 260) {
        $curY = $pdf->StartLetterPage($fam->getID(), $fam->getName(), $fam->Address1(), $fam->getAddress2(), $fam->getCity(), $fam->getState(), $fam->getZip(), $fam->getCountry());
    }

    $ormFamilyMembers = PersonQuery::create()
        ->filterByFamId($fam->getId())
        ->orderByFmrId()
        ->find();

    foreach ($ormFamilyMembers as $member) {
        // Get the Groups this Person is assigned to
        $ormAssignedGroups = GroupQuery::create()
            ->leftJoinPerson2group2roleP2g2r()
            ->addAlias('role', ListOptionTableMap::TABLE_NAME)
            ->addMultipleJoin(array(
                    array('person2group2role_p2g2r.RoleId', ListOptionTableMap::alias('role', ListOptionTableMap::COL_LST_OPTIONID)),
                    array(ListOptionTableMap::Alias("role", ListOptionTableMap::COL_LST_ID), GroupTableMap::COL_GRP_ROLELISTID)
                )
                , Criteria::LEFT_JOIN)
            ->addAsColumn('RoleName', ListOptionTableMap::alias('role', ListOptionTableMap::COL_LST_OPTIONNAME))
            ->where('person2group2role_p2g2r.PersonId = ' . $member->getId())
            ->orderByName()
            ->find();

        if ($ormAssignedGroups->count() > 0) {
            $groupStr = _("Assigned groups for")." " . $member->getFirstName() . ' ' . $member->getLastName() . ': ';

            foreach ($ormAssignedGroups as $group) {
                $groupStr .= $group->getName() . ' (' . _($group->getRoleName()) . ') ';
            }

            $pdf->WriteAt(SystemConfig::getValue('leftX'), $curY, $groupStr);
            $curY += 2 * SystemConfig::getValue('incrementY');
        }
    }

    if ($curY > 183) { // This insures the trailer information fits continuously on the page (3 inches of "footer"
        $curY = $pdf->StartLetterPage($fam->getID(), $fam->getName(), $fam->getAddress1(), $fam->getAddress2(), $fam->getCity(), $fam->getState(), $fam->getZip(), $fam->getCountry());
    }
    $pdf->FinishPage($curY);

    if (count($emaillist) > 0) {
        header('Pragma: public');  // Needed for IE when using a shared SSL certificate

        $doc = $pdf->Output('ConfirmReportEmail-' . $fam->getID() . '-' . date(SystemConfig::getValue("sDateFilenameFormat")) . '.pdf', 'S');

        $subject = $fam->getName() . ' Family Information Review';

        if ($_GET['updated']) {
            $subject = $subject . ' ** Updated **';
        }

        $mail = new FamilyVerificationEmail($emaillist, $fam->getName());
        $filename = 'ConfirmReportEmail-' . $fam->getName() . '-' . date(SystemConfig::getValue("sDateFilenameFormat")) . '.pdf';
        $mail->addStringAttachment($doc, $filename);

        if ($mail->send()) {
            $familiesEmailed = $familiesEmailed + 1;
        } else {
            LoggerUtils::getAppLogger()->error($mail->getError());
        }
    }
}


if ($_GET['familyId']) {
    RedirectUtils::Redirect('FamilyView.php?FamilyID=' . $_GET['familyId'] . '&PDFEmailed=' . $familyEmailSent);
} /*else {
    RedirectUtils::Redirect('v2/familylist/AllPDFsEmailed/'.$familiesEmailed);
}*/
