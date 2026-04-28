<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CommonHelpersTest extends CIUnitTestCase
{
    public function testLoanAliasUsesThreeDigitsAndCustomerInitials(): void
    {
        $this->assertSame('PS-005-LZ', loan_alias(5, 'Luis Manuel Zorrilla'));
    }

    public function testCode39SvgReturnsSvgMarkup(): void
    {
        $svg = code39_svg('PS-005-LZ');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('PS-005-LZ', $svg);
    }

    public function testLoanAliasUsesCustomerInitials(): void
    {
        $this->assertSame('LZ', customer_initials('Luis Manuel Zorrilla'));
    }
}
