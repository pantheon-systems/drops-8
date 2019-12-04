<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Encoder;

use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Encodes JSON data.
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class JsonEncode implements EncoderInterface
{
    private $options;

    public function __construct($bitmask = 0)
    {
        $this->options = $bitmask;
    }

    /**
     * Encodes PHP data to a JSON string.
     *
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = [])
    {
        $context = $this->resolveContext($context);
        $options = $context['json_encode_options'];

        try {
            $encodedJson = json_encode($data, $options);
        } catch (\JsonException $e) {
            throw new NotEncodableValueException($e->getMessage(), 0, $e);
        }

        if (\PHP_VERSION_ID >= 70300 && (JSON_THROW_ON_ERROR & $options)) {
            return $encodedJson;
        }

        if (JSON_ERROR_NONE !== json_last_error() && (false === $encodedJson || !($options & JSON_PARTIAL_OUTPUT_ON_ERROR))) {
            throw new NotEncodableValueException(json_last_error_msg());
        }

        return $encodedJson;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return JsonEncoder::FORMAT === $format;
    }

    /**
     * Merge default json encode options with context.
     *
     * @return array
     */
    private function resolveContext(array $context = [])
    {
        return array_merge(['json_encode_options' => $this->options], $context);
    }
}
