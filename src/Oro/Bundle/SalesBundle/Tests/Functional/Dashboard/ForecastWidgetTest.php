<?php

namespace Oro\Bundle\SalesBundle\Tests\Functional\Dashboard;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\DashboardBundle\Tests\Functional\AbstractWidgetTestCase;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\SalesBundle\Tests\Functional\Fixture\LoadForecastWidgetFixtures;

/**
 * @dbIsolationPerTest
 */
class ForecastWidgetTest extends AbstractWidgetTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?string $initialTimezone;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(['debug' => false], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadForecastWidgetFixtures::class]);

        $this->initialTimezone = self::getConfigManager()->get('oro_locale.timezone');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        if ($configManager->get('oro_locale.timezone') !== $this->initialTimezone) {
            $configManager->set('oro_locale.timezone', $this->initialTimezone);
            $configManager->flush();
        }
    }

    /**
     * @dataProvider getTimeFilter
     */
    public function testCloseDateFilterSuccess(
        int $dateRangeType,
        string $timezone,
        array $value,
        int $inProgressCount
    ): void {
        $widget = $this->getReference('widget_forecast');

        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.timezone', $timezone);
        $configManager->flush();

        $this->configureWidget($widget, [
            'forecast_of_opportunities[dateRange][part]'  => 'value',
            'forecast_of_opportunities[dateRange][type]'  => $dateRangeType,
            'forecast_of_opportunities[subWidgets][items][0][id]' => 'in_progress',
            'forecast_of_opportunities[subWidgets][items][0][order]' => 0,
            'forecast_of_opportunities[subWidgets][items][0][show]' => 'on'
        ]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_dashboard_itemized_data_widget',
                [
                    'widget' => 'forecast_of_opportunities',
                    'bundle' => 'OroDashboard',
                    'name' => 'bigNumbers',
                    '_widgetId' => $widget->getId()
                ]
            )
        );
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Failed in getting widget view');

        $subWidgetValue = $crawler->filter('.big-numbers-items > li .value')->text();

        $this->assertEquals(
            $inProgressCount,
            $subWidgetValue,
            '"In progress" metric is not correct. Check calculation in ForecastProvider!'
        );
    }

    public function getTimeFilter(): array
    {
        return [
            'Close Date: All time' => [
                'date_range_type' => AbstractDateFilterType::TYPE_ALL_TIME,
                'timezone' => 'UTC',
                'value' => [],
                'in_progress_count' => 3,
            ],
            'Close Date: This month' => [
                'date_range_type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                'timezone' => 'UTC',
                'value' => [],
                'in_progress_count' => 2,
            ],
            'Close Date: This month in custom timezone' => [
                'date_range_type' => AbstractDateFilterType::TYPE_THIS_MONTH,
                'timezone' => 'America/Los_Angeles',
                'value' => [],
                'in_progress_count' => 2,
            ]
        ];
    }
}
