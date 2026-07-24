<?php

namespace App\Tests\Unit\Service\Signalement\Suivi;

use App\Entity\Suivi;
use App\Service\Signalement\Suivi\SuiviMentionExtractor;
use PHPUnit\Framework\TestCase;

class SuiviMentionExtractorTest extends TestCase
{
    private SuiviMentionExtractor $suiviMentionExtractor;

    protected function setUp(): void
    {
        $this->suiviMentionExtractor = new SuiviMentionExtractor();
    }

    public function testExtractFromDescription(): void
    {
        $suivi = (new Suivi())
            ->setDescription(
                'Merci de vérifier avec <span class="mention" data-partner-id="3">@Partenaire 13-02</span>'
                .' et <span class="mention" data-partner-id="4">@Partenaire 13-03</span>,'
                .' merci <span class="mention" data-partner-id="3">@Partenaire 13-02</span>'
            )
            ->setType(Suivi::TYPE_PARTNER);

        // dédoublonné, peu importe le nombre de fois où un même partenaire est mentionné
        $this->assertEqualsCanonicalizing([3, 4], $this->suiviMentionExtractor->extract($suivi));
    }

    public function testExtractReturnsEmptyWhenNoMention(): void
    {
        $suivi = (new Suivi())
            ->setDescription('Un suivi sans aucune mention de partenaire')
            ->setType(Suivi::TYPE_PARTNER);

        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));
    }

    public function testExtractReturnsEmptyWhenVisibleForUsagerOrBailleur(): void
    {
        $suivi = (new Suivi())
            ->setDescription('<span class="mention" data-partner-id="3">@Partenaire 13-02</span>')
            ->setType(Suivi::TYPE_PARTNER)
            ->setIsVisibleForUsager(true);
        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));

        $suivi = (new Suivi())
            ->setDescription('<span class="mention" data-partner-id="3">@Partenaire 13-02</span>')
            ->setType(Suivi::TYPE_PARTNER)
            ->setIsVisibleForBailleur(true);
        $this->assertSame([], $this->suiviMentionExtractor->extract($suivi));
    }
}
