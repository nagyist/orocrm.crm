<?php

namespace Oro\Bundle\SalesBundle\Tests\Unit\Dashboard\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Query\CurrencyQueryBuilderTransformerInterface;
use Oro\Bundle\DashboardBundle\Filter\DateFilterProcessor;
use Oro\Bundle\EntityExtendBundle\Twig\EnumExtension;
use Oro\Bundle\SalesBundle\Dashboard\Provider\WidgetOpportunityByLeadSourceProvider;
use Oro\Bundle\SalesBundle\Entity\Opportunity;
use Oro\Bundle\SalesBundle\Entity\Repository\OpportunityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetOpportunityByLeadSourceProviderTest extends TestCase
{
    /**
     * @dataProvider opportunitiesBySourceDataProvider
     */
    public function testSortByValue(array $inputData): void
    {
        $provider = $this->getProvider($inputData);

        $data = $provider->getChartData([], []);

        $this->assertSame(['source' => 'affiliate', 'value' => 19], $data[1]);
        $this->assertSame(['source' => 'direct_mail', 'value' => 15], $data[2]);
        $this->assertSame(['source' => 'website', 'value' => 12], $data[3]);
    }

    /**
     * @dataProvider opportunitiesBySourceDataProvider
     */
    public function testCreateOthersCategoryWithExcludedSources(array $inputData): void
    {
        $provider = $this->getProvider($inputData);
        $data = $provider->getChartData([], [], ['affiliate', 'partner', 'website']);
        $others = array_pop($data);

        $this->assertEquals(37, $others['value']);
        self::assertStringContainsString('others', $others['source']);
    }

    /**
     * @dataProvider opportunitiesBySourceDataProvider
     */
    public function testAddSmallSourceValuesOverLimitToOthersCategory(array $inputData): void
    {
        $data = array_merge(
            [
                // fill up to hardcoded limit of 10
                ['source' => 'source6', 'value' => 10],
                ['source' => 'source7', 'value' => 10],
                ['source' => 'source8', 'value' => 10],
                ['source' => 'source9', 'value' => 10],
                // below sources should be summarized into others category
                ['source' => 'source10', 'value' => 1],
                ['source' => 'source11', 'value' => 2],
                ['source' => 'source12', 'value' => 3],
                ['source' => 'source13', 'value' => 5],
            ],
            $inputData
        );
        $provider = $this->getProvider($data);

        $data = $provider->getChartData([], []);
        $others = array_pop($data);

        $this->assertEquals(11, $others['value']);
        self::assertStringContainsString('others', $others['source']);
    }

    /**
     * @dataProvider opportunitiesBySourceDataProvider
     */
    public function testCreateUnclassifiedCategoryWithEmptySources(array $inputData): void
    {
        $provider = $this->getProvider($inputData);
        $data = $provider->getChartData([], []);
        $unclassified = array_shift($data);

        $this->assertEquals(27, $unclassified['value']);
        self::assertStringContainsString('unclassified', $unclassified['source']);
    }

    /**
     * @dataProvider opportunitiesBySourceDataProvider
     */
    public function testFilterOutZeroSources(array $inputData): void
    {
        $provider = $this->getProvider($inputData);
        $data = $provider->getChartData([], []);

        $this->assertCount(6, $data);
    }

    public function opportunitiesBySourceDataProvider(): array
    {
        return [
            ['data' => [
                ['source' => 'direct_mail', 'value' => 15],
                ['source' => 'affiliate', 'value' => 19],
                ['source' => null, 'value' => 27],
                ['source' => 'partner', 'value' => 6],
                ['source' => 'calls', 'value' => 0],
                ['source' => 'website', 'value' => 12],
                ['source' => 'email_marketing', 'value' => 10],
            ]]
        ];
    }


    public function testCreateUnclassifiedCategoryQueryByAmount(): void
    {
        $doctrine = $this->getDoctrine(['source' => 'direct_mail', 'value' => 15]);
        $opportunityRepo = $doctrine->getRepository(Opportunity::class);

        $aclHelper = $this->createMock(AclHelper::class);
        $processor = $this->createMock(DateFilterProcessor::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $opportunityRepo->expects($this->once())
            ->method('getOpportunitiesGroupByLeadSourceQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);
        $aclHelper->expects($this->once())
            ->method('apply')
            ->willReturn($query);

        // check if the query is built correctly with enum status
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('SUM(CASE WHEN JSON_EXTRACT(o.serialized_data, \'status\') = \'opportunity_status.won\'
                THEN () ELSE () END) as value');
        $enumTranslator = $this->createMock(EnumExtension::class);
        $enumTranslator->expects(self::any())
            ->method('transEnum')
            ->willReturnArgument(0);
        $qbTransformer = $this->createMock(CurrencyQueryBuilderTransformerInterface::class);

        $provider = new WidgetOpportunityByLeadSourceProvider(
            $doctrine,
            $aclHelper,
            $processor,
            $translator,
            $enumTranslator,
            $qbTransformer
        );
        $data = $provider->getChartData([], [], [], true);

        $this->assertIsArray($data);
    }

    private function getProvider(array $data): WidgetOpportunityByLeadSourceProvider
    {
        $doctrine = $this->getDoctrine($data);
        $aclHelper = $this->createMock(AclHelper::class);
        $processor = $this->createMock(DateFilterProcessor::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $enumTranslator = $this->createMock(EnumExtension::class);
        $enumTranslator->expects($this->any())
            ->method('transEnum')
            ->willReturnArgument(0);

        $qbTransformer = $this->createMock(CurrencyQueryBuilderTransformerInterface::class);

        return new WidgetOpportunityByLeadSourceProvider(
            $doctrine,
            $aclHelper,
            $processor,
            $translator,
            $enumTranslator,
            $qbTransformer
        );
    }

    private function getDoctrine(array $data): ManagerRegistry
    {
        $repo = $this->createMock(OpportunityRepository::class);
        $repo->expects($this->any())
            ->method('getOpportunitiesCountGroupByLeadSource')
            ->willReturn($data);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        return $doctrine;
    }
}
