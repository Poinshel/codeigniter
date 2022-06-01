<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Mailer;

use CodeIgniter\Mailer\Exceptions\MailerException;

class Address
{
    /**
     * Characters to trim from display names.
     */
    private const TRIM = ' \'"';

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $name;

    /**
     * Validates the email and stores the values.
     *
     * @throws MailerException
     */
    final public function __construct(string $email, ?string $name = null)
    {
        $this->email = trim($email);

        if (! service('validation')->check($this->email, 'required|valid_email')) {
            throw MailerException::forInvalidAddress($email);
        }

        $this->name = isset($name) ? trim($name, self::TRIM) : null;
    }

    /**
     * Creates a new Address from a simple or full email address string.
     *
     * @return static
     */
    public static function create(string $address)
    {
        return new static(...array_values(self::split($address)));
    }

    /**
     * Creates an array of Addresses from an array of inputs.
     * Accounts for the niche case of a single CSV of addresses
     *
     * @see Email::to()
     *
     * @param string[] $addresses
     *
     * @return static[]
     */
    public static function createArray(array $addresses)
    {
        // Check for a CSV first
        if (count($addresses) === 1) {
            $element = reset($addresses);

            if (is_string($element) && strpos($element, ',') !== false) {
                $addresses = preg_split('/[\n\t\v,]/', $element, -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        return array_map('self::create', $addresses);
    }

    /**
     * Parses an address into an email and (optional) display name.
     * Trims content but does no validation or encoding.
     */
    final public static function split(string $address): array
    {
        if (preg_match('/\<(.*)\>/', $address, $matches)) {
            return [
                'email' => trim($matches[1]),
                'name'  => trim(substr($address, 0, -1 * strlen($matches[0])), self::TRIM),
            ];
        }

        return [
            'email' => trim($address),
            'name'  => null,
        ];
    }

    /**
     * Combines an email and (optional) display name into an address
     * with wrapping quotes and angle brackets.
     * Trims content but does no validation or encoding.
     */
    final public static function merge(string $email, ?string $name = null): string
    {
        $email = trim($email);

        if (null === $name) {
            return $email;
        }

        // If there is no name then finish
        $name = trim($name, self::TRIM);
        if ($name === '') {
            return $email;
        }

        // Wrap the name in quotes and prepend it to the email
        return '"' . $name . '" <' . $email . '>';
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string|null $name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return self::merge($this->email, $this->name);
    }
}