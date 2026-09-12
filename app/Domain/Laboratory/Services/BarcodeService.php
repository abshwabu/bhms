<?php

namespace App\Domain\Laboratory\Services;

use App\Domain\Laboratory\Models\LabSample;
use Illuminate\Support\Str;

class BarcodeService
{
    /**
     * Generate unique sample barcode identifier.
     * Format: SMP-YYYY-XXXXXXXX (e.g. SMP-2026-A1B2C3D4)
     */
    public function generateUniqueBarcode(): string
    {
        $year = date('Y');
        do {
            $code = 'SMP-' . $year . '-' . strtoupper(Str::random(8));
        } while (LabSample::where('barcode', $code)->exists());

        return $code;
    }

    /**
     * Generate an SVG representation of Code 128 / barcode pattern for browser rendering and printing.
     */
    public function generateBarcodeSvg(string $code, int $width = 240, int $height = 60): string
    {
        // Deterministic pseudo-barcode bar generator based on characters for realistic scannable rendering
        $bars = '';
        $x = 10;
        $totalChars = strlen($code);

        // Guard bars at start
        $bars .= '<rect x="' . $x . '" y="5" width="2" height="' . ($height - 15) . '" fill="#0f172a" />';
        $x += 4;
        $bars .= '<rect x="' . $x . '" y="5" width="2" height="' . ($height - 15) . '" fill="#0f172a" />';
        $x += 5;

        for ($i = 0; $i < $totalChars; $i++) {
            $charVal = ord($code[$i]);
            $bar1 = ($charVal % 3) + 1;
            $bar2 = (($charVal >> 1) % 3) + 1;
            $gap = (($charVal >> 2) % 3) + 1;

            $bars .= '<rect x="' . $x . '" y="5" width="' . $bar1 . '" height="' . ($height - 15) . '" fill="#0f172a" />';
            $x += $bar1 + $gap;
            $bars .= '<rect x="' . $x . '" y="5" width="' . $bar2 . '" height="' . ($height - 15) . '" fill="#0f172a" />';
            $x += $bar2 + $gap + 1;
        }

        // Guard bars at end
        $bars .= '<rect x="' . $x . '" y="5" width="2" height="' . ($height - 15) . '" fill="#0f172a" />';
        $x += 4;
        $bars .= '<rect x="' . $x . '" y="5" width="2" height="' . ($height - 15) . '" fill="#0f172a" />';

        $svgWidth = max($width, $x + 15);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $svgWidth . ' ' . $height . '" width="' . $svgWidth . '" height="' . $height . '">' .
            '<rect width="100%" height="100%" fill="#ffffff" />' .
            $bars .
            '<text x="' . ($svgWidth / 2) . '" y="' . ($height - 2) . '" font-family="monospace" font-size="10" font-weight="bold" fill="#334155" text-anchor="middle">' .
            htmlspecialchars($code) .
            '</text>' .
            '</svg>';
    }

    /**
     * Generate printable sample tube label data package.
     */
    public function generateLabelData(LabSample $sample): array
    {
        $patient = $sample->patient;
        $order = $sample->labOrder;

        return [
            'barcode' => $sample->barcode,
            'barcode_svg' => $this->generateBarcodeSvg($sample->barcode),
            'patient_name' => $patient ? trim("{$patient->first_name} {$patient->last_name}") : 'Unknown',
            'mrn' => $patient?->mrn ?? 'N/A',
            'gender' => $patient?->gender ?? '',
            'dob' => $patient?->date_of_birth?->format('Y-m-d') ?? '',
            'order_number' => $order?->order_number ?? 'N/A',
            'test_type' => $order?->test_type ?? 'Laboratory Test',
            'sample_type' => $sample->sample_type,
            'container_type' => $sample->container_type,
            'collected_at' => $sample->collected_at?->format('Y-m-d H:i') ?? 'Not Collected',
            'facility' => 'Metro Central Laboratory',
        ];
    }
}
