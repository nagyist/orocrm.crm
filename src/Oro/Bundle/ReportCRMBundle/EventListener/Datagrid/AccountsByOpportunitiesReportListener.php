<?php

namespace Oro\Bundle\ReportCRMBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\SalesBundle\Entity\Opportunity;

/**
 * AccountsByOpportunitiesReportListener class
 */
class AccountsByOpportunitiesReportListener
{
    const GRAND_TOTAL_PATH = '[totals][grand_total][columns]';
    const COLUMNS_PATH = '[columns]';
    const SORTERS_PATH = '[sorters][columns]';
    const FILTERS_PATH = '[filters][columns]';
    const TOTALOPS_LABEL = 'totalOps';

    protected array $allowedFilterStates = [
        'opportunity_status.won',
        'opportunity_status.lost',
        'opportunity_status.in_progress'
    ];

    public function __construct(protected EnumOptionsProvider $enumProvider)
    {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $enumValues = $this->enumProvider->getEnumChoicesByCode(Opportunity::INTERNAL_STATUS_CODE);
        $config = $event->getConfig();

        $selectTemplate = 'SUM( (CASE WHEN (s.id=\'%s\') THEN 1 ELSE 0 END) ) as %s';
        $grandTotalTemplate = "SUM( (CASE WHEN (s.id='%s') THEN 1 ELSE 0 END) )";

        $selects = $config->getOrmQuery()->getSelect();
        $grandTotals = $config->offsetGetByPath(self::GRAND_TOTAL_PATH, array());
        $columns = $config->offsetGetByPath(self::COLUMNS_PATH, array());
        $sorters = $config->offsetGetByPath(self::SORTERS_PATH, array());
        $filters = $config->offsetGetByPath(self::FILTERS_PATH, array());

        foreach ($enumValues as $text => $id) {
            $label = str_replace('opportunity_status.', '', $id) . 'Count';
            $selects[] = sprintf($selectTemplate, $id, $label);
            $grandTotals[$label] = ['expr' => sprintf($grandTotalTemplate, $id)];
            $columns[$label] = ['label' => $text, 'frontend_type' => 'integer'];
            $sorters[$label] = ['data_name' => $label];
            if (in_array($id, $this->allowedFilterStates, true)) {
                $filters[$label] = [
                    'type' => 'number',
                    'data_name' => $label,
                    'filter_by_having' => true
                ];
            }
        }

        $selects[] = 'COUNT(o.id) as '.self::TOTALOPS_LABEL;
        $grandTotals[self::TOTALOPS_LABEL] = ['expr' => 'COUNT(o.id)'];
        $columns[self::TOTALOPS_LABEL] = [
            'label' => 'oro.reportcrm.datagrid.columns.'.self::TOTALOPS_LABEL,
            'frontend_type' => 'integer'
        ];
        $sorters[self::TOTALOPS_LABEL] = ['data_name' => self::TOTALOPS_LABEL];
        $filters[self::TOTALOPS_LABEL] = [
            'type' => 'number',
            'data_name' => self::TOTALOPS_LABEL,
            'filter_by_having' => true
        ];

        $config->getOrmQuery()->setSelect($selects);
        $config->offsetSetByPath(self::GRAND_TOTAL_PATH, $grandTotals);
        $config->offsetSetByPath(self::COLUMNS_PATH, $columns);
        $config->offsetSetByPath(self::SORTERS_PATH, $sorters);
        $config->offsetSetByPath(self::FILTERS_PATH, $filters);
    }
}
