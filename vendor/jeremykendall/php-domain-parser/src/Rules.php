<?php

/**
 * PHP Domain Parser: Public Suffix List based URL parsing.
 *
 * @see http://github.com/jeremykendall/php-domain-parser for the canonical source repository
 *
 * @copyright Copyright (c) 2017 Jeremy Kendall (http://jeremykendall.net)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pdp;

use Pdp\Exception\CouldNotLoadRules;
use Pdp\Exception\CouldNotResolvePublicSuffix;
use function array_reverse;
use function count;
use function fclose;
use function fopen;
use function implode;
use function in_array;
use function sprintf;
use function stream_get_contents;
use const IDNA_DEFAULT;

/**
 * A class to resolve domain name against the Public Suffix list.
 *
 * @author Jeremy Kendall <jeremy@jeremykendall.net>
 * @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
 */
final class Rules implements PublicSuffixListSection
{
    /**
     * @deprecated 5.3
     */
    const ALL_DOMAINS = 'ALL_DOMAINS';

    /**
     * @internal
     */
    const PSL_SECTION = [self::PRIVATE_DOMAINS, self::ICANN_DOMAINS, ''];

    /**
     * PSL rules as a multidimentional associative array.
     *
     * @var array
     */
    private $rules;

    /**
     * @var int
     */
    private $asciiIDNAOption;

    /**
     * @var int
     */
    private $unicodeIDNAOption;

    /**
     * New instance.
     *
     * @param array $rules
     * @param int   $asciiIDNAOption
     * @param int   $unicodeIDNAOption
     */
    public function __construct(
        array $rules,
        int $asciiIDNAOption = IDNA_DEFAULT,
        int $unicodeIDNAOption = IDNA_DEFAULT
    ) {
        $this->rules = $rules;
        $this->asciiIDNAOption = $asciiIDNAOption;
        $this->unicodeIDNAOption = $unicodeIDNAOption;
    }

    /**
     * Returns a new instance from a file path.
     *
     * @param string        $path
     * @param null|resource $context
     * @param int           $asciiIDNAOption
     * @param int           $unicodeIDNAOption
     *
     * @throws CouldNotLoadRules If the rules can not be loaded from the path
     *
     * @return self
     */
    public static function createFromPath(
        string $path,
        $context = null,
        int $asciiIDNAOption = IDNA_DEFAULT,
        int $unicodeIDNAOption = IDNA_DEFAULT
    ): self {
        $args = [$path, 'r', false];
        if (null !== $context) {
            $args[] = $context;
        }

        if (!($resource = @fopen(...$args))) {
            throw new CouldNotLoadRules(sprintf('`%s`: failed to open stream: No such file or directory', $path));
        }

        $content = stream_get_contents($resource);
        fclose($resource);

        return self::createFromString($content, $asciiIDNAOption, $unicodeIDNAOption);
    }

    /**
     * Returns a new instance from a string.
     *
     * @param string $content
     * @param int    $asciiIDNAOption
     * @param int    $unicodeIDNAOption
     *
     * @return self
     */
    public static function createFromString(
        string $content,
        int $asciiIDNAOption = IDNA_DEFAULT,
        int $unicodeIDNAOption = IDNA_DEFAULT
    ): self {
        static $converter;

        $converter = $converter ?? new Converter();

        return new self($converter->convert($content), $asciiIDNAOption, $unicodeIDNAOption);
    }

    /**
     * {@inheritdoc}
     */
    public static function __set_state(array $properties): self
    {
        return new self(
            $properties['rules'],
            $properties['asciiIDNAOption'] ?? IDNA_DEFAULT,
            $properties['unicodeIDNAOption'] ?? IDNA_DEFAULT
        );
    }

    /**
     * Gets conversion options for idn_to_ascii.
     *
     * combination of IDNA_* constants (except IDNA_ERROR_* constants).
     *
     * @see https://www.php.net/manual/en/intl.constants.php
     *
     * @return int
     */
    public function getAsciiIDNAOption(): int
    {
        return $this->asciiIDNAOption;
    }

    /**
     * Gets conversion options for idn_to_utf8.
     *
     * combination of IDNA_* constants (except IDNA_ERROR_* constants).
     *
     * @see https://www.php.net/manual/en/intl.constants.php
     *
     * @return int
     */
    public function getUnicodeIDNAOption(): int
    {
        return $this->unicodeIDNAOption;
    }

    /**
     * Determines the public suffix for a given domain.
     *
     * @param mixed  $domain
     * @param string $section
     *
     * @throws CouldNotResolvePublicSuffix If the PublicSuffix can not be resolve.
     *
     * @return PublicSuffix
     */
    public function getPublicSuffix($domain, string $section = self::ALL_DOMAINS): PublicSuffix
    {
        $section = $this->validateSection($section);
        if (!$domain instanceof Domain) {
            $domain = new Domain($domain, null, $this->asciiIDNAOption, $this->unicodeIDNAOption);
        }

        if (!$domain->isResolvable()) {
            throw new CouldNotResolvePublicSuffix(sprintf('The domain `%s` can not contain a public suffix', $domain->getContent()));
        }

        return PublicSuffix::createFromDomain($domain->resolve($this->findPublicSuffix($domain, $section)));
    }

    /**
     * Returns PSL info for a given domain.
     *
     * @param mixed  $domain
     * @param string $section
     *
     * @return Domain
     */
    public function resolve($domain, string $section = self::ALL_DOMAINS): Domain
    {
        $section = $this->validateSection($section);
        try {
            $domain = $domain instanceof Domain
                ? $domain
                : new Domain($domain, null, $this->asciiIDNAOption, $this->unicodeIDNAOption);
            if (!$domain->isResolvable()) {
                return $domain;
            }

            return $domain->resolve($this->findPublicSuffix($domain, $section));
        } catch (Exception $e) {
            return new Domain(null, null, $this->asciiIDNAOption, $this->unicodeIDNAOption);
        }
    }

    /**
     * Assert the section status.
     *
     * @param string $section
     *
     * @throws Exception if the submitted section is not supported
     *
     * @return string
     */
    private function validateSection(string $section): string
    {
        if (self::ALL_DOMAINS === $section) {
            $section = '';
        }

        if (in_array($section, self::PSL_SECTION, true)) {
            return $section;
        }

        throw new CouldNotResolvePublicSuffix(sprintf('%s is an unknown Public Suffix List section', $section));
    }

    /**
     * Returns the matched public suffix.
     *
     * @param DomainInterface $domain
     * @param string          $section
     *
     * @return PublicSuffix
     */
    private function findPublicSuffix(DomainInterface $domain, string $section): PublicSuffix
    {
        $asciiDomain = $domain->toAscii();
        $icann = $this->findPublicSuffixFromSection($asciiDomain, self::ICANN_DOMAINS);
        if (self::ICANN_DOMAINS === $section) {
            return $icann;
        }

        $private = $this->findPublicSuffixFromSection($asciiDomain, self::PRIVATE_DOMAINS);
        if (count($private) > count($icann)) {
            return $private;
        }

        if (self::PRIVATE_DOMAINS === $section) {
            return new PublicSuffix($domain->getLabel(0), '', $this->asciiIDNAOption, $this->unicodeIDNAOption);
        }

        return $icann;
    }

    /**
     * Returns the public suffix matched against a given PSL section.
     *
     * @param DomainInterface $domain
     * @param string          $section
     *
     * @return PublicSuffix
     */
    private function findPublicSuffixFromSection(DomainInterface $domain, string $section): PublicSuffix
    {
        $rules = $this->rules[$section] ?? [];
        $matches = [];
        foreach ($domain as $label) {
            //match exception rule
            if (isset($rules[$label], $rules[$label]['!'])) {
                break;
            }

            //match wildcard rule
            if (isset($rules['*'])) {
                $matches[] = $label;
                break;
            }

            //no match found
            if (!isset($rules[$label])) {
                break;
            }

            $matches[] = $label;
            $rules = $rules[$label];
        }

        if ([] === $matches) {
            return new PublicSuffix($domain->getLabel(0), '', $this->asciiIDNAOption, $this->unicodeIDNAOption);
        }

        return new PublicSuffix(
            implode('.', array_reverse($matches)),
            $section,
            $this->asciiIDNAOption,
            $this->unicodeIDNAOption
        );
    }

    /**
     * Sets conversion options for idn_to_ascii.
     *
     * combination of IDNA_* constants (except IDNA_ERROR_* constants).
     *
     * @see https://www.php.net/manual/en/intl.constants.php
     *
     * @param int $asciiIDNAOption
     *
     * @return self
     */
    public function withAsciiIDNAOption(int $asciiIDNAOption): self
    {
        if ($asciiIDNAOption === $this->asciiIDNAOption) {
            return $this;
        }

        $clone = clone $this;
        $clone->asciiIDNAOption = $asciiIDNAOption;

        return $clone;
    }

    /**
     * Sets conversion options for idn_to_utf8.
     *
     * combination of IDNA_* constants (except IDNA_ERROR_* constants).
     *
     * @see https://www.php.net/manual/en/intl.constants.php
     *
     * @param int $unicodeIDNAOption
     *
     * @return self
     */
    public function withUnicodeIDNAOption(int $unicodeIDNAOption): self
    {
        if ($unicodeIDNAOption === $this->unicodeIDNAOption) {
            return $this;
        }

        $clone = clone $this;
        $clone->unicodeIDNAOption = $unicodeIDNAOption;

        return $clone;
    }
}
