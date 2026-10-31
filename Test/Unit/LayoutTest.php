<?php

declare(strict_types=1);

namespace PixelPerfect\DiscountLinesHyvaTheme\Test\Unit;

use PHPUnit\Framework\TestCase;

class LayoutTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../src';

    public function testLayoutOverridesTheCoreDiscountTemplateOnTheCartPage(): void
    {
        $xml = simplexml_load_file(self::MODULE_DIR . '/view/frontend/layout/hyva_checkout_cart_index.xml');
        self::assertCount(1, $xml->xpath('//referenceBlock[@name="discount"][@remove="true"]'));
        $blocks = $xml->xpath('//referenceBlock[@name="checkout.cart.totals"]/block[@name="pp.discount-lines"]');

        self::assertCount(1, $blocks);
        self::assertSame(
            'PixelPerfect_DiscountLinesHyvaTheme::php-cart/totals/discount.phtml',
            (string)$blocks[0]['template']
        );
        self::assertFileExists(self::MODULE_DIR . '/view/frontend/templates/php-cart/totals/discount.phtml');
    }

    public function testGiftCardSegmentGetsATotalsBlock(): void
    {
        $xml = simplexml_load_file(self::MODULE_DIR . '/view/frontend/layout/hyva_checkout_cart_index.xml');
        $blocks = $xml->xpath('//referenceBlock[@name="checkout.cart.totals"]/block[@name="pp.discount-lines.gift-card"]');

        self::assertCount(1, $blocks);
        $template = (string)file_get_contents(
            self::MODULE_DIR . '/view/frontend/templates/php-cart/totals/gift-card.phtml'
        );
        self::assertStringContainsString('segment.code === \'gift_card\'', $template);
        self::assertStringContainsString('x-text="segment.title"', $template);
        self::assertStringNotContainsString('style=', $template);
    }

    public function testStylesheetAndScriptBlockAreRegistered(): void
    {
        $xml = simplexml_load_file(self::MODULE_DIR . '/view/frontend/layout/hyva_checkout_cart_index.xml');

        self::assertCount(1, $xml->xpath('//css[@src="PixelPerfect_DiscountLines::css/discount-lines.css"]'));
        self::assertCount(
            1,
            $xml->xpath('//block[@template="PixelPerfect_DiscountLinesHyvaTheme::js/cart-lines.phtml"]')
        );
        self::assertFileExists(self::MODULE_DIR . '/view/frontend/templates/js/cart-lines.phtml');
    }

    public function testModuleLoadsAfterBaseModuleAndHyvaTheme(): void
    {
        $xml = simplexml_load_file(self::MODULE_DIR . '/etc/module.xml');
        $modules = array_map('strval', $xml->xpath('//sequence/module/@name'));

        self::assertContains('PixelPerfect_DiscountLines', $modules);
        self::assertContains('Hyva_Theme', $modules);
    }

    public function testTemplateFallsBackToTitleAndValueWithoutLines(): void
    {
        $template = (string)file_get_contents(
            self::MODULE_DIR . '/view/frontend/templates/php-cart/totals/discount.phtml'
        );

        self::assertStringContainsString('x-text="segment.title"', $template);
        self::assertStringContainsString('hyva.formatPrice(segment.value)', $template);
        self::assertStringContainsString('segment.code === \'discount\'', $template);
        self::assertStringNotContainsString('style=', $template);
    }

    public function testGiftCardTemplateProvidesTheGiftAndFreeTexts(): void
    {
        $template = (string)file_get_contents(
            self::MODULE_DIR . '/view/frontend/templates/php-cart/totals/gift-card.phtml'
        );

        self::assertStringContainsString('data-label-gift="', $template);
        self::assertStringContainsString('data-label-free="', $template);
    }

    public function testYouSaveIsAPositiveAmount(): void
    {
        $script = (string)file_get_contents(self::MODULE_DIR . '/view/frontend/templates/js/cart-lines.phtml');

        self::assertStringContainsString('hyva.formatPrice(this.saved(segment))', $script);
        self::assertStringNotContainsString('formatPrice(-this.saved', $script);
    }

    public function testOnlyCouponAndGiftLinesWithACodeAreRemovable(): void
    {
        $script = (string)file_get_contents(self::MODULE_DIR . '/view/frontend/templates/js/cart-lines.phtml');

        self::assertStringContainsString(
            "removable: Boolean(line.code) && (line.kind === 'coupon' || line.kind === 'gift')",
            $script
        );
    }

    public function testGermanTranslationCoversEveryString(): void
    {
        $read = static fn (string $f): array => array_column(
            array_map(static fn (string $l): array => str_getcsv($l, ',', '"', ''), file($f)),
            0
        );

        self::assertSame(
            $read(self::MODULE_DIR . '/i18n/en_US.csv'),
            $read(self::MODULE_DIR . '/i18n/de_DE.csv')
        );
    }
}
