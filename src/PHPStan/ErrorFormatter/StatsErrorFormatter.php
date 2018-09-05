<?php declare(strict_types=1);

namespace Symplify\PHPStan\ErrorFormatter;

use Nette\Utils\Strings;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use Symfony\Component\Console\Style\OutputStyle;
use Symplify\PHPStan\Error\ErrorGrouper;

final class StatsErrorFormatter implements ErrorFormatter
{
    /**
     * Number of top errors to display
     * @var int
     */
    private const LIMIT = 10;

    /**
     * @var ErrorGrouper
     */
    private $errorGrouper;

    public function __construct(ErrorGrouper $errorGrouper)
    {
        $this->errorGrouper = $errorGrouper;
    }

    public function formatErrors(AnalysisResult $analysisResult, OutputStyle $outputStyle): int
    {
        if ($analysisResult->getTotalErrorsCount() === 0) {
            $outputStyle->success('No errors');
            // success
            return 0;
        }

        $messagesToFrequency = $this->errorGrouper->groupErrorsToMessagesToFrequency(
            $analysisResult->getFileSpecificErrors()
        );

        $topMessagesToFrequency = $this->cutTopXItems($messagesToFrequency, self::LIMIT);
        $outputStyle->title(sprintf('These are %d most frequent errors', count($topMessagesToFrequency)));

        foreach ($topMessagesToFrequency as $info) {
            $info['files'] = $this->relativizePaths($info['files']);

            $outputStyle->section(sprintf('%d x - "%s"', count($info['files']), $info['message']));
            $outputStyle->listing($info['files']);
        }

        $outputStyle->newLine();
        $outputStyle->error(sprintf('Found %d errors', $analysisResult->getTotalErrorsCount()));

        // fail
        return 1;
    }

    /**
     * @param mixed[] $items
     * @return mixed[]
     */
    private function cutTopXItems(array $items, int $limit): array
    {
        return array_slice($items, 0, min(count($items), $limit), true);
    }

    /**
     * @param string[] $files
     * @return string[]
     */
    private function relativizePaths(array $files): array
    {
        foreach ($files as $i => $file) {
            $files[$i] = Strings::replace($file, '#' . preg_quote(getcwd() . '/') . '#');
        }

        return $files;
    }
}
