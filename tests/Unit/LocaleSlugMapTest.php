<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\CPT\Locale\LocaleSlugMap;

final class LocaleSlugMapTest extends TestCase
{
    public function testSlugForReturnsTheSlugOfTheLocale(): void
    {
        $map = new LocaleSlugMap(['fr_FR' => 'actualite', 'en_EN' => 'news'], 'fr_FR');

        $this->assertSame('actualite', $map->slugFor('fr_FR'));
        $this->assertSame('news', $map->slugFor('en_EN'));
    }

    public function testSlugForFallsBackToTheDefaultLocaleSlug(): void
    {
        $map = new LocaleSlugMap(['fr_FR' => 'actualite', 'en_EN' => 'news'], 'fr_FR');

        $this->assertSame('actualite', $map->slugFor(null));
        $this->assertSame('actualite', $map->slugFor(''));
        $this->assertSame('actualite', $map->slugFor('de_DE'));
    }

    public function testDefaultSlugIsTheDefaultLocaleSlug(): void
    {
        $map = new LocaleSlugMap(['fr_FR' => 'actualite', 'en_EN' => 'news'], 'en_EN');

        $this->assertSame('en_EN', $map->getDefaultLocale());
        $this->assertSame('news', $map->getDefaultSlug());
    }

    public function testDefaultSlugFallsBackToTheFirstSlugWhenDefaultLocaleIsNotInTheMap(): void
    {
        $map = new LocaleSlugMap(['fr_FR' => 'actualite', 'en_EN' => 'news'], 'de_DE');

        $this->assertSame('actualite', $map->getDefaultSlug());
    }

    public function testEmptyOrNonStringSlugsAreDropped(): void
    {
        $map = new LocaleSlugMap(['fr_FR' => 'Actualite ', 'en_EN' => '', 'es_ES' => null, 'it_IT' => 12], 'fr_FR');

        $this->assertSame(['fr_FR'], $map->getLocales());
        $this->assertSame('actualite', $map->slugFor('fr_FR'));
        $this->assertSame('actualite', $map->slugFor('en_EN'), 'dropped locale falls back to the default slug');
    }

    public function testGetAllSlugsAreUniqueAndOrdered(): void
    {
        $map = new LocaleSlugMap(['fr_FR' => 'actualite', 'en_EN' => 'news', 'en_GB' => 'news'], 'fr_FR');

        $this->assertSame(['actualite', 'news'], $map->getAllSlugs());
    }

    public function testFromArgsReturnsNullWithoutLocaleSlugs(): void
    {
        $this->assertNull(LocaleSlugMap::fromArgs([], 'fr_FR'));
        $this->assertNull(LocaleSlugMap::fromArgs(['locale_slugs' => []], 'fr_FR'));
        $this->assertNull(LocaleSlugMap::fromArgs(['locale_slugs' => ['fr_FR' => '']], 'fr_FR'));
    }

    public function testFromArgsBuildsTheMap(): void
    {
        $map = LocaleSlugMap::fromArgs(['locale_slugs' => ['fr_FR' => 'actualite', 'en_EN' => 'news']], 'en_EN');

        $this->assertInstanceOf(LocaleSlugMap::class, $map);
        $this->assertSame('news', $map->getDefaultSlug());
    }
}
