<?php

namespace Oro\Bundle\ChannelBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChannelBundle\Form\EventListener\ChannelTypeSubscriber;
use Oro\Bundle\ChannelBundle\Form\Type\ChannelEntityType;
use Oro\Bundle\ChannelBundle\Form\Type\ChannelType;
use Oro\Bundle\ChannelBundle\Provider\SettingsProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChannelTypeTest extends TestCase
{
    private FormBuilder&MockObject $builder;
    private SettingsProvider&MockObject $settingsProvider;
    private ChannelType $type;
    private ChannelTypeSubscriber $channelTypeSubscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = $this->createMock(FormBuilder::class);
        $this->settingsProvider = $this->createMock(SettingsProvider::class);
        $this->channelTypeSubscriber = $this->createMock(ChannelTypeSubscriber::class);

        $this->settingsProvider->expects($this->any())
            ->method('getChannelTypeChoiceList')
            ->willReturn([]);
        $this->settingsProvider->expects($this->any())
            ->method('getChannelTypeChoiceList')
            ->willReturn([]);

        $this->type = new ChannelType($this->settingsProvider, $this->channelTypeSubscriber);
    }

    public function testBuildForm(): void
    {
        $fields = [];

        $builder = $this->builder;
        $builder->expects($this->exactly(4))
            ->method('add')
            ->willReturnCallback(function ($filedName, $fieldType) use (&$fields, $builder) {
                $fields[$filedName] = $fieldType;

                return $builder;
            });

        $this->type->buildForm($this->builder, []);

        $this->assertSame(
            [
                'name'             => TextType::class,
                'entities'         => ChannelEntityType::class,
                'channelType'      => Select2ChoiceType::class,
                'status'           => HiddenType::class
            ],
            $fields
        );
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testFinishViewShouldNotFailsIfNoOwnerField(): void
    {
        $this->type->finishView(new FormView(), $this->createMock(FormInterface::class), []);
    }

    /**
     * @dataProvider choicesDataProvider
     *
     * @param array $choices
     * @param bool  $shouldAdd
     */
    public function testFinishViewShouldAddHideClassRelyOnChoices(array $choices, $shouldAdd): void
    {
        $mainView                    = new FormView();
        $ownerView                   = new FormView($mainView);
        $mainView->children['owner'] = $ownerView;

        $ownerView->vars['choices'] = $choices;

        $this->type->finishView($mainView, $this->createMock(FormInterface::class), []);

        if ($shouldAdd) {
            $this->assertArrayHasKey('attr', $ownerView->vars);
            $this->assertArrayHasKey('class', $ownerView->vars['attr']);
            self::assertStringContainsString('hide', $ownerView->vars['attr']['class']);
        } else {
            $class = isset($ownerView->vars['attr'], $ownerView->vars['attr']['class'])
                ? $ownerView->vars['attr']['class'] : '';
            $this->assertStringNotContainsString('hide', $class);
        }
    }

    public function testFinishViewShouldAddHideClassAndNotOverrideOld(): void
    {
        $mainView                    = new FormView();
        $ownerView                   = new FormView($mainView);
        $mainView->children['owner'] = $ownerView;
        $ownerView->vars             = ['choices' => [], 'attr' => ['class' => 'testClass']];

        $this->type->finishView($mainView, $this->createMock(FormInterface::class), []);

        self::assertStringContainsString('hide', $ownerView->vars['attr']['class']);
        self::assertStringContainsString('testClass', $ownerView->vars['attr']['class']);
    }

    public function choicesDataProvider(): array
    {
        return [
            'should hide, single choice'            => [
                '$choices'   => ['test'],
                '$shouldAdd' => true
            ],
            'multiple choices, should keep visible' => [
                '$choices'   => ['test', 'test2'],
                '$shouldAdd' => false
            ]
        ];
    }
}
