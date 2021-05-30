<?php
declare(strict_types=1);

namespace Assada\Achievements\Console;

use Assada\Achievements\Achievement;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;

/**
 * Class LoadAchievements
 *
 * @package Assada\Achievements\Console
 */
class LoadAchievementsCommand extends Command
{

    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'achievements:load {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load all Achievements to database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // TODO: Make this recursive in all app/Domain/*/Achievements and app/Domain/*/Achievements/Chains folders

        if (!$this->confirmToProceed()) {
            return;
        }

        $classes = [];


        $files = array_diff(glob('app/Domain/*/Achievements/*Achievement.php'), ['.', '..']);

        foreach ($files as $file) {
            if (!Str::endsWith($file, '.php')) {
                $this->warn(sprintf('File %s is not an php file', $file));
                continue;
            }

            $classes[] = [
              'name'      => basename(Str::before($file, '.php')),
              'namespace' => $this->getNamespace(file_get_contents($file)),
            ];
        }

        $this->info(sprintf('Found %d classes. Instantiating...', count($classes)));

        /** @var Achievement[] $objects */
        $objects = [];

        foreach ($classes as $class) {
            $fullClass = sprintf('%s\%s', $class['namespace'], $class['name']);
            $objects[] = new $fullClass;
        }

        $this->info(sprintf('Created %d objects. Migrating...', count($objects)));

        $bar = $this->output->createProgressBar(count($objects));

        foreach ($objects as $object) {
            $model = $object->getModel();

            $bar->advance();
        }

        $bar->finish();

        $this->line('');
    }

    /**
     * @param $src
     *
     * @return string|null
     */
    private function getNamespace($src)
    : ?string {

        if (preg_match('#^namespace\s+(.+?);$#sm', $src, $m)) {
            return $m[1];
        }
        return null;
    }

}
