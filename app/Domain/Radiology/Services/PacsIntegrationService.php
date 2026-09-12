<?php

namespace App\Domain\Radiology\Services;

use App\Domain\Radiology\Models\ImagingOrder;

class PacsIntegrationService
{
    /**
     * Generate standard DICOM Study Instance UID root.
     */
    public function generateStudyInstanceUid(): string
    {
        // 1.2.826.0.1.3680043.9 is standard medical testing UID root
        $timestamp = microtime(true);
        $seconds = (int)$timestamp;
        $micro = (int)(($timestamp - $seconds) * 1000000);
        $random = mt_rand(100000, 999999);

        return "1.2.826.0.1.3680043.9.{$seconds}.{$micro}.{$random}";
    }

    /**
     * Build external PACS Web Viewer URL (e.g. OHIF Viewer, Orthanc Explorer, Cornerstone).
     */
    public function buildViewerUrl(ImagingOrder $order): string
    {
        $baseUrl = config('services.pacs.viewer_url', 'https://pacs.metrohealth.org/ohif/viewer');
        $studyUid = $order->dicom_study_uid ?? $this->generateStudyInstanceUid();

        return "{$baseUrl}?studyInstanceUID=" . urlencode($studyUid) . "&accession=" . urlencode($order->accession_number);
    }
}
