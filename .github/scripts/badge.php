<?php

/**
 * Creates the badges of the README, which are published to the "badges" branch.
 *
 * Usage:
 *   php .github/scripts/badge.php coverage coverage.xml coverage.svg
 *   php .github/scripts/badge.php status tests success tests.svg
 */
function fail(string $message): never
{
    fwrite(STDERR, $message."\n");

    exit(1);
}

function writeBadge(string $label, string $message, string $color, string $path): void
{
    $labelWidth = (int) ceil(strlen($label) * 6.5) + 10;
    $messageWidth = (int) ceil(strlen($message) * 6.5) + 10;
    $width = $labelWidth + $messageWidth;
    $labelCenter = $labelWidth / 2;
    $messageCenter = $labelWidth + $messageWidth / 2;

    file_put_contents($path, <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="20" role="img" aria-label="{$label}: {$message}">
      <title>{$label}: {$message}</title>
      <linearGradient id="s" x2="0" y2="100%"><stop offset="0" stop-color="#bbb" stop-opacity=".1"/><stop offset="1" stop-opacity=".1"/></linearGradient>
      <clipPath id="r"><rect width="{$width}" height="20" rx="3" fill="#fff"/></clipPath>
      <g clip-path="url(#r)">
        <rect width="{$labelWidth}" height="20" fill="#555"/>
        <rect x="{$labelWidth}" width="{$messageWidth}" height="20" fill="{$color}"/>
        <rect width="{$width}" height="20" fill="url(#s)"/>
      </g>
      <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" font-size="11">
        <text x="{$labelCenter}" y="14">{$label}</text>
        <text x="{$messageCenter}" y="14">{$message}</text>
      </g>
    </svg>

    SVG);
}

$mode = $argv[1] ?? null;

if ($mode === 'coverage' && $argc === 4) {
    [, , $reportPath, $badgePath] = $argv;

    $report = @simplexml_load_file($reportPath);
    $metrics = $report !== false ? $report->project->metrics : null;

    if ($metrics === null || (int) $metrics['statements'] === 0) {
        fail("No coverage metrics found in {$reportPath}.");
    }

    $percentage = (int) floor((int) $metrics['coveredstatements'] / (int) $metrics['statements'] * 100);

    $color = match (true) {
        $percentage >= 80 => '#4c1',
        $percentage >= 60 => '#dfb317',
        default => '#e05d44',
    };

    writeBadge('coverage', "{$percentage}%", $color, $badgePath);

    echo "Coverage: {$percentage}%\n";
} elseif ($mode === 'status' && $argc === 5) {
    [, , $label, $result, $badgePath] = $argv;

    $isPassing = $result === 'success';

    writeBadge($label, $isPassing ? 'passing' : 'failing', $isPassing ? '#4c1' : '#e05d44', $badgePath);
} else {
    fail('Usage: php badge.php coverage <clover.xml> <badge.svg> | php badge.php status <label> <result> <badge.svg>');
}
