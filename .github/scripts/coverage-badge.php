<?php

/**
 * Creates the coverage badge from a Clover report.
 *
 * Usage: php .github/scripts/coverage-badge.php coverage.xml coverage.svg
 */
[, $reportPath, $badgePath] = array_pad($argv, 3, null);

if ($reportPath === null || $badgePath === null) {
    fwrite(STDERR, "Usage: php coverage-badge.php <clover.xml> <badge.svg>\n");

    exit(1);
}

$report = @simplexml_load_file($reportPath);
$metrics = $report !== false ? $report->project->metrics : null;

if ($metrics === null || (int) $metrics['statements'] === 0) {
    fwrite(STDERR, "No coverage metrics found in {$reportPath}.\n");

    exit(1);
}

$percentage = (int) floor((int) $metrics['coveredstatements'] / (int) $metrics['statements'] * 100);

$color = match (true) {
    $percentage >= 80 => '#4c1',
    $percentage >= 60 => '#dfb317',
    default => '#e05d44',
};

$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="104" height="20" role="img" aria-label="coverage: {$percentage}%">
  <title>coverage: {$percentage}%</title>
  <linearGradient id="s" x2="0" y2="100%"><stop offset="0" stop-color="#bbb" stop-opacity=".1"/><stop offset="1" stop-opacity=".1"/></linearGradient>
  <clipPath id="r"><rect width="104" height="20" rx="3" fill="#fff"/></clipPath>
  <g clip-path="url(#r)">
    <rect width="61" height="20" fill="#555"/>
    <rect x="61" width="43" height="20" fill="{$color}"/>
    <rect width="104" height="20" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" font-size="11">
    <text x="30.5" y="14">coverage</text>
    <text x="82.5" y="14">{$percentage}%</text>
  </g>
</svg>

SVG;

file_put_contents($badgePath, $svg);

echo "Coverage: {$percentage}%\n";
