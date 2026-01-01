<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Tests\TestCase;
use VodoCommerce\Console\Commands\MakePluginCommand;
use Illuminate\Filesystem\Filesystem;

class MakePluginCommandTest extends TestCase
{
    public function test_command_has_correct_signature(): void
    {
        $command = new MakePluginCommand(new Filesystem());
        $signature = $command->getName();

        $this->assertEquals('commerce:make:plugin', $signature);
    }

    public function test_command_has_description(): void
    {
        $command = new MakePluginCommand(new Filesystem());
        $description = $command->getDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('plugin', strtolower($description));
    }

    public function test_plugin_types_are_defined(): void
    {
        $reflection = new \ReflectionClass(MakePluginCommand::class);
        $property = $reflection->getProperty('pluginTypes');
        $property->setAccessible(true);

        $command = new MakePluginCommand(new Filesystem());
        $types = $property->getValue($command);

        $this->assertArrayHasKey('payment', $types);
        $this->assertArrayHasKey('shipping', $types);
        $this->assertArrayHasKey('tax', $types);
        $this->assertArrayHasKey('analytics', $types);
        $this->assertArrayHasKey('general', $types);
    }

    public function test_payment_type_has_required_config(): void
    {
        $reflection = new \ReflectionClass(MakePluginCommand::class);
        $property = $reflection->getProperty('pluginTypes');
        $property->setAccessible(true);

        $command = new MakePluginCommand(new Filesystem());
        $types = $property->getValue($command);
        $payment = $types['payment'];

        $this->assertArrayHasKey('contract', $payment);
        $this->assertArrayHasKey('files', $payment);
        $this->assertArrayHasKey('routes', $payment);
        $this->assertArrayHasKey('features', $payment);

        $this->assertEquals('PaymentGatewayContract', $payment['contract']);
        $this->assertContains('webhook', $payment['routes']);
    }

    public function test_shipping_type_has_required_config(): void
    {
        $reflection = new \ReflectionClass(MakePluginCommand::class);
        $property = $reflection->getProperty('pluginTypes');
        $property->setAccessible(true);

        $command = new MakePluginCommand(new Filesystem());
        $types = $property->getValue($command);
        $shipping = $types['shipping'];

        $this->assertEquals('ShippingCarrierContract', $shipping['contract']);
        $this->assertContains('rates', $shipping['features']);
        $this->assertContains('tracking', $shipping['features']);
    }

    public function test_tax_type_has_required_config(): void
    {
        $reflection = new \ReflectionClass(MakePluginCommand::class);
        $property = $reflection->getProperty('pluginTypes');
        $property->setAccessible(true);

        $command = new MakePluginCommand(new Filesystem());
        $types = $property->getValue($command);
        $tax = $types['tax'];

        $this->assertEquals('TaxProviderContract', $tax['contract']);
        $this->assertContains('tax-calculation', $tax['features']);
    }

    public function test_command_has_required_options(): void
    {
        $command = new MakePluginCommand(new Filesystem());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('name'));
        $this->assertTrue($definition->hasOption('type'));
        $this->assertTrue($definition->hasOption('namespace'));
        $this->assertTrue($definition->hasOption('author'));
        $this->assertTrue($definition->hasOption('description'));
        $this->assertTrue($definition->hasOption('force'));
    }

    public function test_type_option_defaults_to_general(): void
    {
        $command = new MakePluginCommand(new Filesystem());
        $definition = $command->getDefinition();
        $typeOption = $definition->getOption('type');

        $this->assertEquals('general', $typeOption->getDefault());
    }

    public function test_command_requires_name_argument(): void
    {
        $command = new MakePluginCommand(new Filesystem());
        $definition = $command->getDefinition();
        $nameArgument = $definition->getArgument('name');

        $this->assertTrue($nameArgument->isRequired());
    }
}
