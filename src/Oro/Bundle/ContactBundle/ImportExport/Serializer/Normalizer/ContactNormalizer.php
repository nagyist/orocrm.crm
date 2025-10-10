<?php

namespace Oro\Bundle\ContactBundle\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Formatter\SocialUrlFormatter;
use Oro\Bundle\ContactBundle\Model\Social;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Root normalizer for contact with plain data
 */
class ContactNormalizer extends ConfigurableEntityNormalizer
{
    const CONTACT_TYPE = Contact::class;

    protected static array $socialFields = [
        Social::TWITTER => 'twitter',
        Social::FACEBOOK => 'facebook',
        Social::GOOGLE_PLUS => 'googlePlus',
        Social::LINKED_IN => 'linkedIn',
    ];

    /**
     * @var SocialUrlFormatter
     */
    protected $socialUrlFormatter;

    /**
     * @var SerializerInterface|NormalizerInterface|DenormalizerInterface
     */
    protected $serializer;

    public function setSocialUrlFormatter(SocialUrlFormatter $socialUrlFormatter)
    {
        $this->socialUrlFormatter = $socialUrlFormatter;
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
        $result = parent::normalize($object, $format, $context);

        foreach (static::$socialFields as $socialType => $fieldName) {
            if (!empty($result[$fieldName])) {
                $result[$fieldName] = $this->socialUrlFormatter->getSocialUrl(
                    $socialType,
                    $result[$fieldName]
                );
            }
        }

        return $result;
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        foreach (static::$socialFields as $socialType => $fieldName) {
            if (!empty($data[$fieldName])) {
                $data[$fieldName] = $this->socialUrlFormatter->getSocialUsername(
                    $socialType,
                    $data[$fieldName]
                );
            }
        }

        return parent::denormalize($data, $type, $format, $context);
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Contact;
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $type === static::CONTACT_TYPE;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Contact::class => false
        ];
    }
}
