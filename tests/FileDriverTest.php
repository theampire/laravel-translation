<?php

use Orchestra\Testbench\TestCase;
use JoeDixon\Translation\Drivers\File;
use JoeDixon\Translation\Exceptions\LanguageExistsException;

class FileDriverTest extends TestCase
{
    private $translation;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();
        app()['path.lang'] = __DIR__ . '/fixtures/lang';
        $this->translation = app()->make('translation');
    }

    protected function getPackageProviders($app)
    {
        return ['JoeDixon\Translation\TranslationServiceProvider'];
    }

    /** @test */
    public function it_returns_all_languages()
    {
        $languages = $this->translation->allLanguages();

        $this->assertEquals($languages->count(), 2);
        $this->assertEquals($languages->toArray(), ['en', 'es']);
    }

    /** @test */
    public function it_returns_all_translations()
    {
        $translations = $this->translation->allTranslations();

        $this->assertEquals($translations->count(), 2);
        $this->assertArraySubset(['en' => ['single' => ['Hello' => 'Hello', "What's up" => "What's up!"], 'group' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]]], $translations->toArray());
        $this->assertArrayHasKey('en', $translations->toArray());
        $this->assertArrayHasKey('es', $translations->toArray());
    }

    /** @test */
    public function it_returns_all_translations_for_a_given_language()
    {
        $translations = $this->translation->allTranslationsFor('en');
        $this->assertEquals($translations->count(), 2);
        $this->assertEquals(['single' => ['Hello' => 'Hello', "What's up" => "What's up!"], 'group' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]], $translations->toArray());
        $this->assertArrayHasKey('single', $translations->toArray());
        $this->assertArrayHasKey('group', $translations->toArray());
    }

    /** @test */
    public function it_throws_an_exception_if_a_language_exists()
    {
        $this->expectException(LanguageExistsException::class);
        $this->translation->addLanguage('en');
    }

    /** @test */
    public function it_can_add_a_new_language()
    {
        $this->translation->addLanguage('fr');

        $this->assertTrue(file_exists(__DIR__ . '/fixtures/lang/fr.json'));
        $this->assertTrue(file_exists(__DIR__ . '/fixtures/lang/fr'));

        rmdir(__DIR__ . '/fixtures/lang/fr');
        unlink(__DIR__ . '/fixtures/lang/fr.json');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_a_group_translation_file()
    {
        $this->translation->addGroupTranslation('es', 'test.hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['group' => ['test' => ['hello' => 'Hola!']]], $translations->toArray());

        unlink(__DIR__ . '/fixtures/lang/es/test.php');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_existing_array_translation_file()
    {
        $this->translation->addGroupTranslation('en', 'test.test', 'Testing');

        $translations = $this->translation->allTranslationsFor('en');

        $this->assertArraySubset(['group' => ['test' => ['hello' => 'Hello', 'whats_up' => 'What\'s up!', 'test' => 'Testing']]], $translations->toArray());

        file_put_contents(
            app()['path.lang'] . '/en/test.php',
            "<?php\n\nreturn " . var_export(['hello' => 'Hello', 'whats_up' => 'What\'s up!'], true) . ';' . \PHP_EOL
        );
    }

    /** @test */
    public function it_can_add_a_new_translation_to_a_json_translation_file()
    {
        $this->translation->addSingleTranslation('es', 'Hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['single' => ['Hello' => 'Hola!']], $translations->toArray());

        unlink(__DIR__ . '/fixtures/lang/es.json');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_existing_json_translation_file()
    {
        $this->translation->addSingleTranslation('en', 'Test', 'Testing');

        $translations = $this->translation->allTranslationsFor('en');

        $this->assertArraySubset(['single' => ['Hello' => 'Hello', 'What\'s up' => 'What\'s up!', 'Test' => 'Testing']], $translations->toArray());

        file_put_contents(
            app()['path.lang'] . '/en.json',
            json_encode((object)['Hello' => 'Hello', 'What\'s up' => 'What\'s up!'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}