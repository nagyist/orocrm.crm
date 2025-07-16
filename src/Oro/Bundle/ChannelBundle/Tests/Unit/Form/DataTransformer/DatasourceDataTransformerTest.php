<?php

namespace Oro\Bundle\ChannelBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ChannelBundle\Form\DataTransformer\DatasourceDataTransformer;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

class DatasourceDataTransformerTest extends TestCase
{
    private const TEST_TYPE = 'testType';
    private const TEST_NAME = 'testName';

    private FormFactoryInterface&MockObject $formFactory;
    private DatasourceDataTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->transformer = new DatasourceDataTransformer($this->formFactory);
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param mixed $data
     * @param mixed $expectedResult
     */
    public function testTransform($data, $expectedResult): void
    {
        $this->assertSame($expectedResult, $this->transformer->transform($data));
    }

    public function transformDataProvider(): array
    {
        $integration = new Integration();
        $integration->setType(self::TEST_TYPE);
        $integration->setName(self::TEST_NAME);

        return [
            'should return null if empty data given' => [
                '$data'           => null,
                '$expectedResult' => null
            ],
            'should return null if bad data given'   => [
                '$data'           => ['testBadData'],
                '$expectedResult' => null
            ],
            'should convert to expected array'       => [
                '$data'           => $integration,
                '$expectedResult' => [
                    'type'       => self::TEST_TYPE,
                    'data'       => null,
                    'identifier' => $integration,
                    'name'       => self::TEST_NAME
                ]
            ]
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(
        mixed $data,
        mixed $expectedResult,
        bool $expectedSubmit = false,
        ?string $expectedException = null
    ): void {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $this->initializeMocks($expectedSubmit, $expectedException);

        $this->assertSame($expectedResult, $this->transformer->reverseTransform($data));
    }
    public function testReverseTransformShouldBindData(): void
    {
        $this->initializeMocks(true);

        $result = $this->transformer->reverseTransform([
            'data' => [
                'name' => self::TEST_NAME,
                'type' => self::TEST_TYPE
            ],
            'identifier' => null
        ]);

        self::assertSame(self::TEST_NAME, $result->getName());
        self::assertSame(self::TEST_TYPE, $result->getType());
    }

    public function reverseTransformDataProvider(): array
    {
        $integration = new Integration();

        return [
            'should return null if empty data given' => [
                'data' => null,
                'expectedResult' => null
            ],
            'should throw exception if bad data given' => [
                'data' => new \stdClass(),
                'expectedResult' => null,
                'expectedSubmit' => false,
                'expectedException' => UnexpectedTypeException::class
            ],
            'should thor exception if invalid data submitted' => [
                'data' => ['data' => new \stdClass(), 'identifier' => null],
                'expectedResult' => null,
                'expectedSubmit' => true,
                'expectedException' => \LogicException::class
            ],
            'should bind on data that comes form setData' => [
                'data' => [
                    'data' => [
                        'name' => self::TEST_NAME,
                        'type' => self::TEST_TYPE
                    ],
                    'identifier' => $integration
                ],
                'expectedResult' => $integration,
                'expectedSubmit' => true,
            ]
        ];
    }

    /**
     * @param bool  $expectedSubmit
     * @param mixed $expectedException
     */
    private function initializeMocks($expectedSubmit, $expectedException = null)
    {
        if ($expectedSubmit) {
            $formMock = $this->createMock(FormInterface::class);

            $data = null;
            $this->formFactory->expects($this->any())
                ->method('create')
                ->with(
                    ChannelType::class,
                    $this->isInstanceOf(Integration::class),
                    ['csrf_protection' => false, 'disable_customer_datasource_types' => false]
                )
                ->willReturnCallback(function ($type, $formData) use (&$data, $formMock) {
                    // capture form data
                    $data = $formData;

                    return $formMock;
                });

            $formMock->expects($this->any())
                ->method('submit')
                ->willReturnCallback(function ($submitted) use (&$data, $formMock) {
                    // emulate submit
                    $accessor = PropertyAccess::createPropertyAccessor();
                    foreach ($submitted as $key => $value) {
                        $accessor->setValue($data, $key, $value);
                    }

                    return $formMock;
                });
            $invalid = null !== $expectedException;
            $formMock->expects($this->any())
                ->method('isValid')
                ->willReturn(!$invalid);

            if ($invalid) {
                $formMock->expects($this->any())
                    ->method('getErrors')
                    ->willReturn(new FormErrorIterator($formMock, ['message']));
            }
        } else {
            $this->formFactory->expects($this->never())
                ->method('create');
        }
    }
}
