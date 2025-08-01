<?php

namespace Oro\Bundle\ActivityContactBundle\Tests\Unit\Api\Processor\GetConfig;

use Oro\Bundle\ActivityContactBundle\Api\Processor\GetConfig\UpdateActivityContactFields;
use Oro\Bundle\ActivityContactBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityContactBundle\Provider\ActivityContactProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use PHPUnit\Framework\MockObject\MockObject;

class UpdateActivityContactFieldsTest extends ConfigProcessorTestCase
{
    private const EXCLUDED_ACTIONS = ['create', 'update'];

    private DoctrineHelper&MockObject $doctrineHelper;
    private ConfigManager&MockObject $configManager;
    private ActivityContactProvider&MockObject $activityContactProvider;
    private UpdateActivityContactFields $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->activityContactProvider = $this->createMock(ActivityContactProvider::class);

        $this->processor = new UpdateActivityContactFields(
            $this->doctrineHelper,
            $this->configManager,
            $this->activityContactProvider,
            self::EXCLUDED_ACTIONS
        );
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'exclusion_policy' => 'none',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => null,
                ActivityScope::LAST_CONTACT_DATE_OUT => null,
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $this->configManager->expects($this->never())
            ->method('hasConfig');

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    ActivityScope::LAST_CONTACT_DATE     => null,
                    ActivityScope::LAST_CONTACT_DATE_IN  => null,
                    ActivityScope::LAST_CONTACT_DATE_OUT => null,
                    ActivityScope::CONTACT_COUNT         => null,
                    ActivityScope::CONTACT_COUNT_IN      => null,
                    ActivityScope::CONTACT_COUNT_OUT     => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => null,
                ActivityScope::LAST_CONTACT_DATE_OUT => null,
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    ActivityScope::LAST_CONTACT_DATE     => null,
                    ActivityScope::LAST_CONTACT_DATE_IN  => null,
                    ActivityScope::LAST_CONTACT_DATE_OUT => null,
                    ActivityScope::CONTACT_COUNT         => null,
                    ActivityScope::CONTACT_COUNT_IN      => null,
                    ActivityScope::CONTACT_COUNT_OUT     => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForNotConfigurableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => null,
                ActivityScope::LAST_CONTACT_DATE_OUT => null,
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME);
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    ActivityScope::LAST_CONTACT_DATE     => null,
                    ActivityScope::LAST_CONTACT_DATE_IN  => null,
                    ActivityScope::LAST_CONTACT_DATE_OUT => null,
                    ActivityScope::CONTACT_COUNT         => null,
                    ActivityScope::CONTACT_COUNT_IN      => null,
                    ActivityScope::CONTACT_COUNT_OUT     => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForNotExtendableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => null,
                ActivityScope::LAST_CONTACT_DATE_OUT => null,
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $expendConfig = new Config(new EntityConfigId('extend', self::TEST_CLASS_NAME));

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn($expendConfig);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    ActivityScope::LAST_CONTACT_DATE     => null,
                    ActivityScope::LAST_CONTACT_DATE_IN  => null,
                    ActivityScope::LAST_CONTACT_DATE_OUT => null,
                    ActivityScope::CONTACT_COUNT         => null,
                    ActivityScope::CONTACT_COUNT_IN      => null,
                    ActivityScope::CONTACT_COUNT_OUT     => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForEntityWithoutActivities()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => null,
                ActivityScope::LAST_CONTACT_DATE_OUT => null,
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $expendConfig = new Config(new EntityConfigId('extend', self::TEST_CLASS_NAME));
        $expendConfig->set('is_extend', true);
        $activityConfig = new Config(new EntityConfigId('activity', self::TEST_CLASS_NAME));

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['extend', self::TEST_CLASS_NAME, $expendConfig],
                ['activity', self::TEST_CLASS_NAME, $activityConfig]
            ]);

        $this->activityContactProvider->expects($this->never())
            ->method('getSupportedActivityClasses');

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    ActivityScope::LAST_CONTACT_DATE     => null,
                    ActivityScope::LAST_CONTACT_DATE_IN  => null,
                    ActivityScope::LAST_CONTACT_DATE_OUT => null,
                    ActivityScope::CONTACT_COUNT         => null,
                    ActivityScope::CONTACT_COUNT_IN      => null,
                    ActivityScope::CONTACT_COUNT_OUT     => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForEntityWithoutSupportedActivities()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => null,
                ActivityScope::LAST_CONTACT_DATE_OUT => null,
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $expendConfig = new Config(new EntityConfigId('extend', self::TEST_CLASS_NAME));
        $expendConfig->set('is_extend', true);
        $activityConfig = new Config(new EntityConfigId('activity', self::TEST_CLASS_NAME));
        $activityConfig->set('activities', ['Test\Activity1']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['extend', self::TEST_CLASS_NAME, $expendConfig],
                ['activity', self::TEST_CLASS_NAME, $activityConfig]
            ]);

        $this->activityContactProvider->expects($this->once())
            ->method('getSupportedActivityClasses')
            ->willReturn(['Test\Activity2']);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    ActivityScope::LAST_CONTACT_DATE     => null,
                    ActivityScope::LAST_CONTACT_DATE_IN  => null,
                    ActivityScope::LAST_CONTACT_DATE_OUT => null,
                    ActivityScope::CONTACT_COUNT         => null,
                    ActivityScope::CONTACT_COUNT_IN      => null,
                    ActivityScope::CONTACT_COUNT_OUT     => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForEntityWithSupportedActivities()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => null,
                ActivityScope::LAST_CONTACT_DATE_OUT => null,
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $expendConfig = new Config(new EntityConfigId('extend', self::TEST_CLASS_NAME));
        $expendConfig->set('is_extend', true);
        $activityConfig = new Config(new EntityConfigId('activity', self::TEST_CLASS_NAME));
        $activityConfig->set('activities', ['Test\Activity1', 'Test\Activity2']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['extend', self::TEST_CLASS_NAME, $expendConfig],
                ['activity', self::TEST_CLASS_NAME, $activityConfig]
            ]);

        $this->activityContactProvider->expects($this->once())
            ->method('getSupportedActivityClasses')
            ->willReturn(['Test\Activity2']);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'lastContactedDate'    => ['property_path' => ActivityScope::LAST_CONTACT_DATE],
                    'lastContactedDateIn'  => ['property_path' => ActivityScope::LAST_CONTACT_DATE_IN],
                    'lastContactedDateOut' => ['property_path' => ActivityScope::LAST_CONTACT_DATE_OUT],
                    'timesContacted'       => ['property_path' => ActivityScope::CONTACT_COUNT],
                    'timesContactedIn'     => ['property_path' => ActivityScope::CONTACT_COUNT_IN],
                    'timesContactedOut'    => ['property_path' => ActivityScope::CONTACT_COUNT_OUT]
                ]
            ],
            $configObject
        );
    }

    /**
     * @dataProvider excludedActionProvider
     */
    public function testProcessForExcludedAction($action)
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE     => null,
                ActivityScope::LAST_CONTACT_DATE_IN  => [
                    'exclude' => true
                ],
                ActivityScope::LAST_CONTACT_DATE_OUT => [
                    'exclude' => false
                ],
                ActivityScope::CONTACT_COUNT         => null,
                ActivityScope::CONTACT_COUNT_IN      => null,
                ActivityScope::CONTACT_COUNT_OUT     => null
            ]
        ];

        $expendConfig = new Config(new EntityConfigId('extend', self::TEST_CLASS_NAME));
        $expendConfig->set('is_extend', true);
        $activityConfig = new Config(new EntityConfigId('activity', self::TEST_CLASS_NAME));
        $activityConfig->set('activities', ['Test\Activity1', 'Test\Activity2']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['extend', self::TEST_CLASS_NAME, $expendConfig],
                ['activity', self::TEST_CLASS_NAME, $activityConfig]
            ]);

        $this->activityContactProvider->expects($this->once())
            ->method('getSupportedActivityClasses')
            ->willReturn(['Test\Activity2']);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->context->setTargetAction($action);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'lastContactedDate'    => [
                        'form_options'  => ['mapped' => false],
                        'property_path' => ActivityScope::LAST_CONTACT_DATE
                    ],
                    'lastContactedDateIn'  => [
                        'exclude'       => true,
                        'property_path' => ActivityScope::LAST_CONTACT_DATE_IN
                    ],
                    'lastContactedDateOut' => [
                        'form_options'  => ['mapped' => false],
                        'property_path' => ActivityScope::LAST_CONTACT_DATE_OUT
                    ],
                    'timesContacted'       => [
                        'form_options'  => ['mapped' => false],
                        'property_path' => ActivityScope::CONTACT_COUNT
                    ],
                    'timesContactedIn'     => [
                        'form_options'  => ['mapped' => false],
                        'property_path' => ActivityScope::CONTACT_COUNT_IN
                    ],
                    'timesContactedOut'    => [
                        'form_options'  => ['mapped' => false],
                        'property_path' => ActivityScope::CONTACT_COUNT_OUT
                    ]
                ]
            ],
            $configObject
        );
    }

    public static function excludedActionProvider(): array
    {
        return array_map(
            function ($action) {
                return [$action];
            },
            self::EXCLUDED_ACTIONS
        );
    }
    public function testProcessForEntityWithSupportedActivitiesAndHasConflicts()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                ActivityScope::LAST_CONTACT_DATE => null,
                'lastContactedDate'              => null,
                ActivityScope::CONTACT_COUNT     => ['property_path' => 'field1']
            ]
        ];

        $expendConfig = new Config(new EntityConfigId('extend', self::TEST_CLASS_NAME));
        $expendConfig->set('is_extend', true);
        $activityConfig = new Config(new EntityConfigId('activity', self::TEST_CLASS_NAME));
        $activityConfig->set('activities', ['Test\Activity1', 'Test\Activity2']);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['extend', self::TEST_CLASS_NAME, $expendConfig],
                ['activity', self::TEST_CLASS_NAME, $activityConfig]
            ]);

        $this->activityContactProvider->expects($this->once())
            ->method('getSupportedActivityClasses')
            ->willReturn(['Test\Activity2']);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    ActivityScope::LAST_CONTACT_DATE => null,
                    'lastContactedDate'              => null,
                    ActivityScope::CONTACT_COUNT     => ['property_path' => 'field1']
                ]
            ],
            $configObject
        );
    }
}
