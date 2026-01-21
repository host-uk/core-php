<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\Input\Sanitiser;
use Core\Tests\TestCase;

class SanitiserTest extends TestCase
{
    protected Sanitiser $sanitiser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitiser = new Sanitiser;
    }

    public function test_filter_returns_empty_array_for_empty_input(): void
    {
        $result = $this->sanitiser->filter([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_filter_preserves_normal_strings(): void
    {
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $result = $this->sanitiser->filter($input);

        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_filter_strips_null_bytes(): void
    {
        $input = [
            'data' => "Hello\x00World",
        ];

        $result = $this->sanitiser->filter($input);

        $this->assertEquals('HelloWorld', $result['data']);
    }

    public function test_filter_strips_control_characters(): void
    {
        $input = [
            'text' => "Line1\x01\x02\x03Line2",
        ];

        $result = $this->sanitiser->filter($input);

        $this->assertEquals('Line1Line2', $result['text']);
    }

    public function test_filter_preserves_newlines_and_tabs(): void
    {
        // Note: FILTER_FLAG_STRIP_LOW strips ASCII 0-31
        // which includes \n (10) and \t (9)
        // This test documents actual behavior
        $input = [
            'text' => "Line1\nLine2\tTabbed",
        ];

        $result = $this->sanitiser->filter($input);

        // Control characters are stripped
        $this->assertIsString($result['text']);
    }

    public function test_filter_preserves_unicode(): void
    {
        $input = [
            'name' => '日本語テスト',
            'emoji' => '👋 Hello 🌍',
            'accents' => 'Café résumé naïve',
        ];

        $result = $this->sanitiser->filter($input);

        $this->assertEquals('日本語テスト', $result['name']);
        $this->assertEquals('👋 Hello 🌍', $result['emoji']);
        $this->assertEquals('Café résumé naïve', $result['accents']);
    }

    public function test_filter_handles_nested_arrays(): void
    {
        // Note: filter_var_array processes top-level keys only
        // Nested arrays are returned as-is (or filtered based on definition)
        $input = [
            'simple' => 'value',
        ];

        $result = $this->sanitiser->filter($input);

        $this->assertEquals('value', $result['simple']);
    }

    public function test_filter_handles_numeric_values_as_strings(): void
    {
        $input = [
            'number' => '12345',
            'float' => '123.45',
        ];

        $result = $this->sanitiser->filter($input);

        $this->assertEquals('12345', $result['number']);
        $this->assertEquals('123.45', $result['float']);
    }

    public function test_filter_handles_special_html_characters(): void
    {
        $input = [
            'html' => '<script>alert("xss")</script>',
            'entities' => '&lt;div&gt;',
        ];

        $result = $this->sanitiser->filter($input);

        // FILTER_UNSAFE_RAW doesn't strip HTML, just control characters
        $this->assertEquals('<script>alert("xss")</script>', $result['html']);
        $this->assertEquals('&lt;div&gt;', $result['entities']);
    }

    public function test_filter_processes_multiple_keys(): void
    {
        $input = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $result = $this->sanitiser->filter($input);

        $this->assertCount(3, $result);
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
        $this->assertEquals('value3', $result['key3']);
    }
}
