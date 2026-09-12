<?php

namespace App\Domain\Clinical\Services;

use App\Domain\Clinical\Models\Prescription;
use App\Domain\Patient\Models\Patient;
use App\Domain\Patient\Models\PatientAllergy;

class DrugInteractionService
{
    /**
     * Common drug-allergen cross-sensitivity map.
     */
    protected array $allergenClassMap = [
        'penicillin' => [
            'amoxicillin', 'ampicillin', 'augmentin', 'penicillin', 'piperacillin',
            'tazobactam', 'amoxicillin-clavulanate', 'oxacillin', 'dicloxacillin', 'amoxil'
        ],
        'beta_lactam' => [
            'amoxicillin', 'ampicillin', 'augmentin', 'penicillin', 'piperacillin',
            'cephalexin', 'ceftriaxone', 'cefuroxime', 'cefazolin', 'meropenem'
        ],
        'cephalosporin' => [
            'cephalexin', 'ceftriaxone', 'cefuroxime', 'cefazolin', 'cefepime', 'cefdinir', 'cefaclor'
        ],
        'sulfa' => [
            'bactrim', 'septra', 'sulfamethoxazole', 'trimethoprim', 'sulfasalazine',
            'furosemide', 'hydrochlorothiazide', 'glipizide'
        ],
        'nsaid' => [
            'aspirin', 'ibuprofen', 'naproxen', 'ketorolac', 'diclofenac',
            'indomethacin', 'meloxicam', 'celecoxib', 'piroxicam', 'advil', 'motrin', 'aleve'
        ],
        'aspirin' => [
            'aspirin', 'acetylsalicylic acid', 'ibuprofen', 'naproxen', 'ketorolac', 'diclofenac'
        ],
        'opioid' => [
            'morphine', 'codeine', 'tramadol', 'fentanyl', 'oxycodone',
            'hydrocodone', 'hydromorphone', 'meperidine', 'methadone'
        ],
        'fluoroquinolone' => [
            'ciprofloxacin', 'levofloxacin', 'moxifloxacin', 'ofloxacin', 'norfloxacin'
        ],
        'macrolide' => [
            'azithromycin', 'clarithromycin', 'erythromycin', 'zithromax'
        ],
        'statin' => [
            'atorvastatin', 'simvastatin', 'rosuvastatin', 'pravastatin', 'lipitor', 'zocor'
        ],
        'ace_inhibitor' => [
            'lisinopril', 'enalapril', 'ramipril', 'captopril', 'benazepril'
        ],
    ];

    /**
     * Drug-drug critical interaction matrix.
     */
    protected array $drugDrugRules = [
        [
            'drug_a' => ['warfarin', 'coumadin'],
            'drug_b' => ['aspirin', 'ibuprofen', 'naproxen', 'ketorolac', 'diclofenac', 'meloxicam', 'indomethacin'],
            'severity' => 'high',
            'title' => 'Severe Bleeding Risk: Warfarin + NSAID/Aspirin',
            'mechanism' => 'Synergistic hypoprothrombinemic and antiplatelet effects produce dramatically heightened risk of fatal gastrointestinal or intracerebral hemorrhage.',
            'recommendation' => 'Avoid co-prescription. Use paracetamol/acetaminophen for analgesia or monitor INR closely.',
        ],
        [
            'drug_a' => ['sildenafil', 'tadalafil', 'vardenafil', 'viagra', 'cialis'],
            'drug_b' => ['nitroglycerin', 'isosorbide mononitrate', 'isosorbide dinitrate', 'nitrostat', 'nitrates'],
            'severity' => 'high',
            'title' => 'Refractory Hypotension: PDE-5 Inhibitor + Nitrates',
            'mechanism' => 'Profound potentiation of cGMP-mediated vasodilation leading to life-threatening cardiovascular collapse and fatal hypotension.',
            'recommendation' => 'Absolute contraindication. Do not co-prescribe within 24 to 48 hours of each other.',
        ],
        [
            'drug_a' => ['lisinopril', 'enalapril', 'ramipril', 'captopril', 'losartan', 'valsartan'],
            'drug_b' => ['spironolactone', 'eplerenone', 'potassium chloride', 'k-dur'],
            'severity' => 'high',
            'title' => 'Severe Hyperkalemia Risk: Renin-Angiotensin Blocker + Potassium Sparing Agent',
            'mechanism' => 'Dual inhibition of aldosterone-mediated potassium excretion precipitates severe hyperkalemia, cardiac conduction blocks, and arrest.',
            'recommendation' => 'Measure baseline serum potassium and creatinine; recheck within 3 to 7 days.',
        ],
        [
            'drug_a' => ['tramadol', 'ultram'],
            'drug_b' => ['fluoxetine', 'sertraline', 'paroxetine', 'citalopram', 'escitalopram', 'venlafaxine', 'duloxetine'],
            'severity' => 'high',
            'title' => 'Serotonin Syndrome & Seizure Risk: Tramadol + Serotonergic Antidepressant',
            'mechanism' => 'Combined serotonin reuptake inhibition lowers seizure threshold and increases risk of potentially fatal Serotonin Syndrome.',
            'recommendation' => 'Avoid combination. Consider alternative analgesics without serotonergic properties.',
        ],
        [
            'drug_a' => ['methotrexate'],
            'drug_b' => ['ibuprofen', 'naproxen', 'aspirin', 'ketorolac', 'diclofenac'],
            'severity' => 'high',
            'title' => 'Methotrexate Toxicity: Methotrexate + NSAIDs',
            'mechanism' => 'NSAIDs decrease renal tubular clearance of methotrexate, causing myelosuppression, severe pancytopenia, and acute kidney injury.',
            'recommendation' => 'Contraindicated with oncologic methotrexate doses. Withhold NSAIDs or closely monitor renal and hematologic labs.',
        ],
        [
            'drug_a' => ['simvastatin', 'atorvastatin', 'lovastatin'],
            'drug_b' => ['clarithromycin', 'erythromycin'],
            'severity' => 'high',
            'title' => 'Rhabdomyolysis Hazard: Statin + Strong CYP3A4 Inhibitor Macrolide',
            'mechanism' => 'Clarithromycin strongly inhibits hepatic CYP3A4 metabolism of statins, elevating statin blood levels up to tenfold and causing acute myopathy and renal failure.',
            'recommendation' => 'Temporarily suspend statin therapy during antibiotic course, or prescribe azithromycin instead.',
        ],
        [
            'drug_a' => ['ciprofloxacin', 'cipro'],
            'drug_b' => ['theophylline', 'theo-24'],
            'severity' => 'high',
            'title' => 'Theophylline Intoxication: Ciprofloxacin + Theophylline',
            'mechanism' => 'Ciprofloxacin inhibits CYP1A2 metabolism of theophylline, leading to toxic plasma theophylline levels, agitation, and fatal cardiac arrhythmias.',
            'recommendation' => 'Reduce theophylline dose by 50% and monitor plasma levels, or select levofloxacin or alternative antimicrobial.',
        ],
        [
            'drug_a' => ['digoxin', 'lanoxin'],
            'drug_b' => ['amiodarone', 'cordarone'],
            'severity' => 'high',
            'title' => 'Digoxin Toxicity: Amiodarone + Digoxin',
            'mechanism' => 'Amiodarone inhibits renal and biliary P-glycoprotein efflux of digoxin, doubling serum digoxin levels within days.',
            'recommendation' => 'Reduce digoxin dose by 50% upon initiating amiodarone and monitor digoxin serum concentrations.',
        ],
        [
            'drug_a' => ['clopidogrel', 'plavix'],
            'drug_b' => ['omeprazole', 'prilosec'],
            'severity' => 'moderate',
            'title' => 'Decreased Antiplatelet Activation: Clopidogrel + Omeprazole',
            'mechanism' => 'Omeprazole competitively inhibits CYP2C19, reducing bioactivation of clopidogrel and increasing secondary ischemic cardiovascular events.',
            'recommendation' => 'Substitute with pantoprazole or famotidine, which exert minimal CYP2C19 inhibitory action.',
        ],
        [
            'drug_a' => ['levothyroxine', 'synthroid'],
            'drug_b' => ['calcium carbonate', 'ferrous sulfate', 'iron', 'calcium'],
            'severity' => 'moderate',
            'title' => 'Reduced Thyroid Absorption: Levothyroxine + Cation Supplements',
            'mechanism' => 'Calcium and iron cations form insoluble chelate complexes with thyroxine in the gastrointestinal tract, causing clinical hypothyroidism.',
            'recommendation' => 'Instruct patient to separate administration times by at least 4 hours.',
        ],
    ];

    /**
     * Analyze a candidate prescription or set of medications for:
     * 1. Patient Allergy conflicts
     * 2. Drug-Drug Interactions (intra-order and against existing active meds)
     * 3. Therapeutic Duplications
     *
     * @param string|Patient $patient Patient instance or patient UUID
     * @param array $items Array of items: [['medication_name' => ..., 'generic_name' => ...]]
     * @return array Array of structured alerts
     */
    public function check(string|Patient $patient, array $items): array
    {
        $patientModel = is_string($patient) ? Patient::find($patient) : $patient;
        if (!$patientModel) {
            return [];
        }

        $alerts = [];

        // 1. Check Drug-Allergy Warnings
        $allergyAlerts = $this->checkAllergies($patientModel, $items);
        $alerts = array_merge($alerts, $allergyAlerts);

        // 2. Check Drug-Drug Interactions within the current prescribed items
        $intraOrderAlerts = $this->checkDrugDrugWithinOrder($items);
        $alerts = array_merge($alerts, $intraOrderAlerts);

        // 3. Check Drug-Drug Interactions against patient's active medication history / existing active prescriptions
        $historicalAlerts = $this->checkAgainstExistingMedications($patientModel, $items);
        $alerts = array_merge($alerts, $historicalAlerts);

        // 4. Check Therapeutic Duplication (e.g. 2 NSAIDs in same prescription)
        $duplicationAlerts = $this->checkTherapeuticDuplication($items);
        $alerts = array_merge($alerts, $duplicationAlerts);

        return $alerts;
    }

    /**
     * Check items against patient's recorded allergies.
     */
    protected function checkAllergies(Patient $patient, array $items): array
    {
        $alerts = [];
        $allergies = PatientAllergy::where('patient_id', $patient->id)
            ->whereIn('status', ['active', 'suspected'])
            ->get();

        foreach ($items as $item) {
            $medName = strtolower(trim($item['medication_name'] ?? ''));
            $genericName = strtolower(trim($item['generic_name'] ?? ''));

            foreach ($allergies as $allergy) {
                $allergen = strtolower(trim($allergy->allergen));

                $isMatch = false;

                // Direct substring or exact match
                if (
                    str_contains($medName, $allergen) ||
                    str_contains($allergen, $medName) ||
                    (!empty($genericName) && (str_contains($genericName, $allergen) || str_contains($allergen, $genericName)))
                ) {
                    $isMatch = true;
                }

                // Cross-sensitivity allergen class mapping check
                if (!$isMatch) {
                    foreach ($this->allergenClassMap as $classKey => $drugs) {
                        if (str_contains($allergen, $classKey) || $allergen === $classKey) {
                            foreach ($drugs as $drug) {
                                if (str_contains($medName, $drug) || str_contains($genericName, $drug)) {
                                    $isMatch = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }

                if ($isMatch) {
                    $severity = in_array(strtolower($allergy->severity), ['severe', 'life_threatening'], true) ? 'high' : 'high';
                    
                    $alerts[] = [
                        'type' => 'drug_allergy',
                        'severity' => $severity,
                        'drug' => $item['medication_name'],
                        'allergen' => $allergy->allergen,
                        'reaction' => $allergy->reaction ?? 'Hypersensitivity Reaction',
                        'title' => "Documented Drug Allergy: {$allergy->allergen}",
                        'description' => "Patient has a documented allergy to '{$allergy->allergen}' (Reaction: {$allergy->reaction}, Severity: {$allergy->severity}). Prescribing '{$item['medication_name']}' presents a direct risk of severe allergic reaction or anaphylaxis.",
                        'recommendation' => "Avoid prescribing {$item['medication_name']}. Select an alternative pharmacological class with no cross-reactivity.",
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Check drug-drug interactions among items in the current prescription.
     */
    protected function checkDrugDrugWithinOrder(array $items): array
    {
        $alerts = [];
        $count = count($items);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $drug1 = $items[$i];
                $drug2 = $items[$j];

                $matchedRule = $this->matchInteractionRule($drug1, $drug2);
                if ($matchedRule) {
                    $alerts[] = [
                        'type' => 'drug_drug',
                        'severity' => $matchedRule['severity'],
                        'drug_a' => $drug1['medication_name'],
                        'drug_b' => $drug2['medication_name'],
                        'title' => $matchedRule['title'],
                        'description' => $matchedRule['mechanism'],
                        'recommendation' => $matchedRule['recommendation'],
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Check prescribed items against active medications already on patient chart.
     */
    protected function checkAgainstExistingMedications(Patient $patient, array $items): array
    {
        $alerts = [];

        // Fetch recent active/finalized prescriptions in the last 30 days
        $activePrescriptions = Prescription::where('patient_id', $patient->id)
            ->where('status', 'finalized')
            ->where('prescribed_at', '>=', now()->subDays(30))
            ->with('items')
            ->get();

        $existingItems = [];
        foreach ($activePrescriptions as $prescription) {
            foreach ($prescription->items as $item) {
                $existingItems[] = [
                    'medication_name' => $item->medication_name,
                    'generic_name' => $item->generic_name,
                ];
            }
        }

        foreach ($items as $newItem) {
            foreach ($existingItems as $existingItem) {
                // If same item, skip (avoid duplicate self-interaction)
                if (strtolower(trim($newItem['medication_name'])) === strtolower(trim($existingItem['medication_name']))) {
                    continue;
                }

                $matchedRule = $this->matchInteractionRule($newItem, $existingItem);
                if ($matchedRule) {
                    $alerts[] = [
                        'type' => 'drug_drug_concurrent',
                        'severity' => $matchedRule['severity'],
                        'drug_a' => $newItem['medication_name'],
                        'drug_b' => $existingItem['medication_name'],
                        'title' => "Concurrent Medication Interaction: {$matchedRule['title']}",
                        'description' => "Patient has an active prescription for '{$existingItem['medication_name']}'. {$matchedRule['mechanism']}",
                        'recommendation' => $matchedRule['recommendation'],
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Check if multiple drugs from identical therapeutic class are ordered.
     */
    protected function checkTherapeuticDuplication(array $items): array
    {
        $alerts = [];
        $classesFound = [];

        foreach ($items as $item) {
            $name = strtolower(trim(($item['medication_name'] ?? '') . ' ' . ($item['generic_name'] ?? '')));

            foreach ($this->allergenClassMap as $className => $classDrugs) {
                foreach ($classDrugs as $classDrug) {
                    if (str_contains($name, $classDrug)) {
                        $classesFound[$className][] = $item['medication_name'];
                        break;
                    }
                }
            }
        }

        foreach ($classesFound as $className => $drugNames) {
            $uniqueDrugs = array_unique($drugNames);
            if (count($uniqueDrugs) > 1) {
                $alerts[] = [
                    'type' => 'therapeutic_duplication',
                    'severity' => 'moderate',
                    'class' => strtoupper($className),
                    'drugs' => $uniqueDrugs,
                    'title' => "Therapeutic Duplication Detected (" . strtoupper($className) . ")",
                    'description' => "Multiple agents from the " . strtoupper($className) . " class (" . implode(', ', $uniqueDrugs) . ") are prescribed concurrently, increasing toxicity without proven clinical benefit.",
                    'recommendation' => "Select a single appropriate agent or document clinical indication for combination therapy.",
                ];
            }
        }

        return $alerts;
    }

    /**
     * Check if two drugs match any interaction rule.
     */
    protected function matchInteractionRule(array $drug1, array $drug2): ?array
    {
        $name1 = strtolower(trim(($drug1['medication_name'] ?? '') . ' ' . ($drug1['generic_name'] ?? '')));
        $name2 = strtolower(trim(($drug2['medication_name'] ?? '') . ' ' . ($drug2['generic_name'] ?? '')));

        foreach ($this->drugDrugRules as $rule) {
            $matchA1 = $this->containsAny($name1, $rule['drug_a']);
            $matchB2 = $this->containsAny($name2, $rule['drug_b']);

            $matchA2 = $this->containsAny($name2, $rule['drug_a']);
            $matchB1 = $this->containsAny($name1, $rule['drug_b']);

            if (($matchA1 && $matchB2) || ($matchA2 && $matchB1)) {
                return $rule;
            }
        }

        return null;
    }

    protected function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, strtolower($needle))) {
                return true;
            }
        }
        return false;
    }
}
