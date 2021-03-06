<?php

namespace EcclesiaCRM\Search;

use EcclesiaCRM\Search\BaseSearchRes;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\LoggerUtils;
use Propel\Runtime\ActiveQuery\Criteria;
use EcclesiaCRM\PledgeQuery;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\SessionUser;


class PledgeSearchRes extends BaseSearchRes
{
    public function __construct($global = false)
    {
        $this->name = _('Pledges');
        parent::__construct($global, 'Pledges');
    }

    public function buildSearch(string $qry)
    {
        if ( SessionUser::getUser()->isFinanceEnabled() && SystemConfig::getBooleanValue('bEnabledFinance') )
        {
            //Pledges Search
            if (SystemConfig::getBooleanValue("bSearchIncludePledges"))
            {
                try {
                    $searchLikeString = '%'.$qry.'%';

                    $Pledges = PledgeQuery::create();

                    if (SystemConfig::getBooleanValue('bGDPR') && !SystemConfig::getBooleanValue('bSearchFinancesGDPR')) {// normally gdpr is more important than finances ??? Plogel, a deposit can always be seen but not a pledge
                        $Pledges
                            ->useFamilyQuery()
                            ->filterByDateDeactivated(null)
                            ->endUse()
                            ->_and();
                    }

                    $Pledges->useFamilyQuery()
                            ->filterByName($searchLikeString, Criteria::LIKE)
                        ->endUse();

                    $Pledges->leftJoinDeposit()
                        ->withColumn('Deposit.Id', 'DepositId')
                        ->groupByDepositId();

                    if (!$this->global_search) {
                        $Pledges->limit(SystemConfig::getValue("iSearchIncludePledgesMax"));
                    }

                    $Pledges->find();

                    if (!is_null($Pledges))
                    {
                        $id=1;

                        foreach ($Pledges as $Pledge) {
                            $elt = ['id'=>'pledges-'.$id++,
                                'text'=>$Pledge->getFamily()->getName()." ("._("Deposit")." #".$Pledge->getDepositId().")",
                                'uri'=> SystemURLs::getRootPath() . "/PledgeEditor.php?linkBack=DepositSlipEditor.php?DepositSlipID=".$Pledge->getDepositId()."&GroupKey=".$Pledge->getGroupkey()];

                            if (!is_null($Pledge->getDepositId())) {
                                if ($this->global_search) {
                                    $res = "";
                                    if (SessionUser::getUser()->isShowCartEnabled()) {
                                        $res = '<a href="' . $elt['uri'] . '" data-toggle="tooltip" data-placement="top" data-original-title="' . _('Edit') . '">';
                                    }

                                    $res .= '<span class="fa-stack">'
                                        .'<i class="fa fa-square fa-stack-2x"></i>'
                                        .'<i class="fa fa-pencil fa-stack-1x fa-inverse"></i>'
                                        .'</span>';

                                    if (SessionUser::getUser()->isShowCartEnabled()) {
                                        $res .= '</a>&nbsp;';
                                    }

                                    $elt = [
                                        'id' => $Pledge->getDepositId(),
                                        'img' => '<img src="/Images/Bank.png" class="initials-image direct-chat-img " width="10px" height="10px">',
                                        'searchresult' => '<a href="'.SystemURLs::getRootPath()."/PledgeEditor.php?linkBack=DepositSlipEditor.php?DepositSlipID=".$Pledge->getDepositId()."&GroupKey=".$Pledge->getGroupkey().'" data-toggle="tooltip" data-placement="top" data-original-title="'._('Edit').'">'.$Pledge->getFamily()->getName()." ("._("Deposit")." #".$Pledge->getDepositId().")".'</a>',
                                        'address' => "",
                                        'type' => " "._($this->getGlobalSearchType()),
                                        'realType' => $this->getGlobalSearchType(),
                                        'Gender' => "",
                                        'Classification' => "",
                                        'ProNames' => "",
                                        'FamilyRole' => "",
                                        "members" => "",
                                        'actions' => $res
                                    ];
                                }

                                array_push($this->results, $elt);
                            }
                        }
                    }
                } catch (Exception $e) {
                    LoggerUtils::getAppLogger()->warn($e->getMessage());
                }
            }
        }
    }
}
