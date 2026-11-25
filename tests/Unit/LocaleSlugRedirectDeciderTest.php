<?php

declare(strict_types=1);

namespace WonderWp\Component\CPT\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WonderWp\Component\CPT\Locale\LocaleSlugRedirectDecider;

final class LocaleSlugRedirectDeciderTest extends TestCase
{
    private const KNOWN = ['actualite', 'news', 'actualites', 'wwp-actu'];

    private function decide(string $path, string $canonical = 'actualite', bool $preview = false): ?string
    {
        return (new LocaleSlugRedirectDecider())->decide($path, $canonical, self::KNOWN, $preview);
    }

    public function testCanonicalSlugIsLeftAlone(): void
    {
        $this->assertNull($this->decide('actualite/mon-actu'));
        $this->assertNull($this->decide('actualite/mon-actu/'));
    }

    public function testLegacyAliasRedirectsToTheCanonicalSlug(): void
    {
        $this->assertSame('actualite/mon-actu', $this->decide('wwp-actu/mon-actu'));
        $this->assertSame('actualite/mon-actu/', $this->decide('actualites/mon-actu/'));
    }

    public function testSlugOfAnotherLanguageRedirectsToTheCanonicalSlug(): void
    {
        $this->assertSame('actualite/mon-actu/', $this->decide('news/mon-actu/'));
        $this->assertSame('news/my-news/', $this->decide('actualite/my-news/', 'news'));
    }

    public function testPaginationAndSubPathAreKept(): void
    {
        $this->assertSame('actualite/mon-actu/2/', $this->decide('news/mon-actu/2/'));
        $this->assertSame('actualite/mon-actu/page/2/', $this->decide('wwp-actu/mon-actu/page/2/'));
    }

    public function testNeverRedirectsAfterTheRewrite(): void
    {
        $target = $this->decide('news/mon-actu/');

        $this->assertNotNull($target);
        $this->assertNull($this->decide($target), 'the redirect target must be stable (no loop)');
    }

    public function testUnknownPrefixIsLeftAlone(): void
    {
        $this->assertNull($this->decide('agenda/mon-actu/'));
        $this->assertNull($this->decide(''));
        $this->assertNull($this->decide('actualite'));
    }

    public function testPreviewIsNeverRedirected(): void
    {
        $this->assertNull($this->decide('news/mon-actu/', 'actualite', true));
    }

    public function testSegmentComparisonIsCaseInsensitive(): void
    {
        $this->assertNull($this->decide('Actualite/mon-actu/'));
        $this->assertSame('actualite/mon-actu/', $this->decide('News/mon-actu/'));
    }

    public function testLeadingSlashIsTolerated(): void
    {
        $this->assertSame('actualite/mon-actu/', $this->decide('/news/mon-actu/'));
    }
}
