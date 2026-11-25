<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\CPT\Locale\LocaleSlugMap;
use WonderWp\Component\CPT\Locale\PostTypeLinkFilter;

final class PostTypeLinkFilterTest extends TestCase
{
    private PostTypeLinkFilter $filter;

    protected function setUp(): void
    {
        $GLOBALS['wwp_test_post_meta'] = [
            10 => ['news_locale' => 'fr_FR'],
            20 => ['news_locale' => 'en_EN'],
            40 => ['news_locale' => 'de_DE'],
        ];
        $GLOBALS['wwp_test_locale'] = 'fr_FR';

        $this->filter = new PostTypeLinkFilter(
            'wwp-actu',
            new LocaleSlugMap(['fr_FR' => 'actualite', 'en_EN' => 'news'], 'fr_FR'),
            'news_locale'
        );
    }

    private function post(int $id, string $type = 'wwp-actu'): object
    {
        return (object) ['ID' => $id, 'post_type' => $type];
    }

    public function testFrenchPostGetsTheFrenchSlug(): void
    {
        $this->assertSame(
            'https://example.test/actualite/mon-actu/',
            $this->filter->filter('https://example.test/actualite/mon-actu/', $this->post(10))
        );
    }

    public function testEnglishPostGetsTheEnglishSlug(): void
    {
        $this->assertSame(
            'https://example.test/news/my-news/',
            $this->filter->filter('https://example.test/actualite/my-news/', $this->post(20))
        );
    }

    public function testResultDoesNotDependOnTheRequestLocale(): void
    {
        $results = [];
        foreach (['fr_FR', 'en_EN'] as $requestLocale) {
            $GLOBALS['wwp_test_locale'] = $requestLocale;
            $results[] = $this->filter->filter('https://example.test/actualite/my-news/', $this->post(20));
        }

        $this->assertSame($results[0], $results[1]);
        $this->assertSame('https://example.test/news/my-news/', $results[0]);
    }

    public function testPostWithoutLocaleMetaUsesTheDefaultLocale(): void
    {
        $this->assertSame(
            'https://example.test/actualite/sans-meta/',
            $this->filter->filter('https://example.test/actualite/sans-meta/', $this->post(30))
        );
    }

    public function testPostWithUnknownLocaleMetaUsesTheDefaultLocale(): void
    {
        $this->assertSame(
            'https://example.test/actualite/allemand/',
            $this->filter->filter('https://example.test/actualite/allemand/', $this->post(40))
        );
    }

    public function testOtherPostTypesAreUntouched(): void
    {
        $link = 'https://example.test/actualite/page-normale/';

        $this->assertSame($link, $this->filter->filter($link, $this->post(20, 'page')));
    }

    public function testLinkWithoutTheCanonicalPrefixIsUntouched(): void
    {
        $plain = 'https://example.test/?wwp-actu=my-news&p=20';

        $this->assertSame($plain, $this->filter->filter($plain, $this->post(20)));
    }

    public function testOnlyTheFirstPathSegmentIsRewritten(): void
    {
        $this->assertSame(
            'https://example.test/news/actualite-record/',
            $this->filter->filter('https://example.test/actualite/actualite-record/', $this->post(20))
        );
    }

    public function testSegmentIsMatchedExactly(): void
    {
        $link = 'https://example.test/actualites/x/';

        $this->assertSame($link, $this->filter->filter($link, $this->post(20)), 'a longer segment must not be rewritten');
    }

    public function testNonPostValueIsReturnedUntouched(): void
    {
        $link = 'https://example.test/actualite/x/';

        $this->assertSame($link, $this->filter->filter($link, null));
    }
}
