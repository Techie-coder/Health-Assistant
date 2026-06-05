<?php
declare(strict_types=1);

class TriageEngine
{
    private const EMERGENCY_FLAGS = [
        'chest pain', 'difficulty breathing', 'severe bleeding', 'unconscious',
        'seizure', 'stroke', 'cannot breathe', 'heart attack', 'not breathing',
        'maumivu ya kifua', 'shida ya kupumua', 'damu nyingi', 'hajitambui',
        'kifafa', 'mshtuko wa moyo', 'hupumui'
    ];

    private const HIGH_FLAGS = [
        'high fever', 'blood in stool', 'blood in urine', 'severe pain',
        'dehydration', 'pregnant and bleeding', 'homa kali', 'damu',
        'maumivu makali', 'upungufu wa maji mwilini', 'mimba na damu'
    ];

    private const CONDITION_RULES = [
        ['keywords' => ['fever', 'headache', 'chills', 'sweating', 'homa', 'maumivu ya kichwa', 'baridi'], 'name' => 'Malaria', 'prob' => 0.68],
        ['keywords' => ['diarrhea', 'vomiting', 'stomach pain', 'kuhara', 'kutapika', 'maumivu ya tumbo'], 'name' => 'Gastroenteritis', 'prob' => 0.58],
        ['keywords' => ['cough', 'sore throat', 'runny nose', 'kikohozi', 'koo', 'pua inatiririka'], 'name' => 'Respiratory infection', 'prob' => 0.52],
        ['keywords' => ['high fever', 'abdominal pain', 'weakness', 'homa kali', 'udhaifu'], 'name' => 'Typhoid', 'prob' => 0.48],
        ['keywords' => ['pregnant', 'bleeding', 'abdominal cramp', 'mimba', 'damu', 'uchungu wa tumbo'], 'name' => 'Maternal health concern', 'prob' => 0.62],
        ['keywords' => ['yellow eyes', 'yellow skin', 'dark urine', 'macho manjano', 'mkojo wa giza'], 'name' => 'Jaundice / Hepatitis', 'prob' => 0.50],
    ];

    private const FIRST_AID = [
        'Malaria' => [
            'en' => 'Rest in shade, drink fluids, take paracetamol for fever. Get a malaria test at a clinic within 24 hours.',
            'sw' => 'Pumzika kwenye kivuli, kunywa maji, tumia paracetamol kwa homa. Fanya kipimo cha malaria kliniki ndani ya masaa 24.'
        ],
        'Gastroenteritis' => [
            'en' => 'Drink ORS frequently. Continue breastfeeding for infants. Seek care if blood in stool or no urination.',
            'sw' => 'Kunywa ORS mara kwa mara. Endelea kunyonyesha watoto wachanga. Tafuta huduma ikiwa damu ipo au haukojoi.'
        ],
        'Respiratory infection' => [
            'en' => 'Rest, drink warm fluids, avoid smoke. Seek care if breathing becomes difficult.',
            'sw' => 'Pumzika, kunywa vinywaji moto, epuka moshi. Tafuta huduma ikiwa kupumua kuna shida.'
        ],
        'Typhoid' => [
            'en' => 'Drink boiled water, rest, eat light food. Visit clinic for blood/stool tests.',
            'sw' => 'Kunywa maji yaliyochemshwa, pumzika, kula chakula kidogo. Tembelea kliniki kwa vipimo.'
        ],
        'Maternal health concern' => [
            'en' => 'Lie on left side, do not delay. Go to nearest facility immediately if bleeding during pregnancy.',
            'sw' => 'Lala upande wa kushoto, usichelewe. Nenda kituo cha afya mara moja ikiwa una damu wakati wa mimba.'
        ],
        'Jaundice / Hepatitis' => [
            'en' => 'Avoid alcohol and traditional herbs. Seek medical evaluation promptly.',
            'sw' => 'Epuka pombe na dawa za kienyeji. Tafuta uchunguzi wa matibabu haraka.'
        ],
        'General illness' => [
            'en' => 'Rest, drink clean water, monitor temperature. Visit clinic if symptoms persist beyond 2 days.',
            'sw' => 'Pumzika, kunywa maji safi, fuatilia joto. Tembelea kliniki ikiwa dalili zinaendelea zaidi ya siku 2.'
        ],
    ];

    private const EDUCATION_TOPICS = [
        'malaria' => [
            'en' => 'Sleep under insecticide-treated nets. Remove standing water near homes. Seek testing within 24 hours of fever.',
            'sw' => 'Lala chini ya chandarua kilichoshwa dawa. Ondoa maji yaliyosimama karibu na nyumba. Fanya kipimo ndani ya masaa 24 baada ya homa.'
        ],
        'hygiene' => [
            'en' => 'Wash hands with soap before eating and after toilet use. Use clean latrines to prevent disease spread.',
            'sw' => 'Osha mikono na sabuni kabla ya kula na baada ya choo. Tumia vyoo safi kuzuia magonjwa.'
        ],
        'nutrition' => [
            'en' => 'Eat vegetables, fruits, beans, and protein daily. Pregnant women need iron-rich foods and clinic visits.',
            'sw' => 'Kula mboga, matunda, maharage, na protini kila siku. Wanawake wajawazito wanahitaji chakula chenye chuma na kliniki.'
        ],
        'immunization' => [
            'en' => 'Vaccinate children on schedule at your local clinic. Vaccines prevent deadly diseases.',
            'sw' => 'Chanja watoto kwa ratiba kliniki ya karibu. Chanjo huzuia magonjwa hatari.'
        ],
        'water' => [
            'en' => 'Boil or treat drinking water. Store in clean covered containers.',
            'sw' => 'Chemsha au tibu maji ya kunywa. Hifadhi kwenye chombo safi kilichofunikwa.'
        ],
        'pregnancy' => [
            'en' => 'Attend all antenatal visits. Report bleeding, severe headache, or swelling immediately.',
            'sw' => 'Hudhuria kliniki za ujauzito zote. Ripoti damu, maumivu makali ya kichwa, au uvimbe mara moja.'
        ],
    ];

    public function analyze(string $symptoms, string $lang, string $ageGroup, int $duration): array
    {
        $lower = mb_strtolower($symptoms);
        $urgency = $this->assessUrgency($lower, $duration, $ageGroup);
        $conditions = $this->predictConditions($lower);
        $top = $conditions[0]['name'] ?? 'General illness';
        $firstAid = self::FIRST_AID[$top][$lang] ?? self::FIRST_AID['General illness'][$lang];

        return [
            'urgency' => $urgency,
            'urgency_label' => $this->urgencyLabel($urgency, $lang),
            'possible_conditions' => $conditions,
            'first_aid' => $firstAid,
            'recommendation' => $this->urgencyRecommendation($urgency, $lang),
            'explanation' => $this->buildExplanation($symptoms, $conditions, $lang),
            'is_emergency' => $urgency === 'emergency',
            'disclaimer' => $lang === 'sw'
                ? 'Hii si utambuzi rasmi wa matibabu. Wasiliana na mtaalamu wa afya.'
                : 'This is not a formal medical diagnosis. Consult a healthcare professional.',
        ];
    }

    public function chat(string $message, string $lang): string
    {
        $lower = mb_strtolower($message);

        foreach (self::EDUCATION_TOPICS as $key => $texts) {
            if (str_contains($lower, $key)) {
                return $texts[$lang];
            }
        }

        if ($this->isEmergencyText($lower)) {
            return $lang === 'sw'
                ? 'Hali hii inaweza kuwa dharura. Piga 112/999 au nenda hospitali ya karibu mara moja.'
                : 'This may be an emergency. Call 112/999 or go to the nearest hospital immediately.';
        }

        $ai = $this->callOpenAI(
            $lang === 'sw'
                ? "Wewe ni msaidizi wa elimu ya afya kwa jamii za vijijini Tanzania. Jibu kwa Kiswahili rahisi. Usitoe utambuzi. Swali: $message"
                : "You are a rural health education assistant in Tanzania. Answer in simple English. No diagnosis. Question: $message"
        );

        return $ai ?? ($lang === 'sw'
            ? 'Tembelea kituo cha afya cha karibu kwa ushauri zaidi.'
            : 'Visit your nearest health facility for more advice.');
    }

    private function isEmergencyText(string $lower): bool
    {
        foreach (self::EMERGENCY_FLAGS as $flag) {
            if (str_contains($lower, $flag)) return true;
        }
        return false;
    }

    private function assessUrgency(string $lower, int $duration, string $ageGroup): string
    {
        if ($this->isEmergencyText($lower)) return 'emergency';

        foreach (self::HIGH_FLAGS as $flag) {
            if (str_contains($lower, $flag)) return 'high';
        }

        if ($ageGroup === 'elderly' && str_contains($lower, 'fever')) return 'high';
        if ($ageGroup === 'child' && (str_contains($lower, 'fever') || str_contains($lower, 'homa'))) return 'high';

        if ($duration >= 5) return 'high';
        if ($duration >= 2) return 'medium';
        return 'low';
    }

    private function predictConditions(string $lower): array
    {
        $results = [];
        foreach (self::CONDITION_RULES as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $results[] = [
                        'name' => $rule['name'],
                        'probability' => $rule['prob'],
                        'confidence' => $rule['prob'] >= 0.55 ? 'moderate' : 'low',
                    ];
                    break;
                }
            }
        }

        if (empty($results)) {
            $results[] = ['name' => 'General illness', 'probability' => 0.40, 'confidence' => 'low'];
        }

        usort($results, fn($a, $b) => $b['probability'] <=> $a['probability']);
        return array_slice($results, 0, 3);
    }

    private function urgencyLabel(string $urgency, string $lang): string
    {
        $labels = [
            'emergency' => ['Emergency', 'Dharura'],
            'high' => ['High urgency', 'Umuhimu wa juu'],
            'medium' => ['Moderate urgency', 'Umuhimu wa wastani'],
            'low' => ['Low urgency', 'Umuhimu wa chini'],
        ];
        [$en, $sw] = $labels[$urgency] ?? $labels['medium'];
        return $lang === 'sw' ? $sw : $en;
    }

    private function urgencyRecommendation(string $urgency, string $lang): string
    {
        $map = [
            'emergency' => ['Go to the nearest hospital immediately. Call emergency services if available.', 'Nenda hospitali ya karibu mara moja. Piga simu ya dharura ikiwa inapatikana.'],
            'high' => ['Visit a health facility within 24 hours.', 'Tembelea kituo cha afya ndani ya masaa 24.'],
            'medium' => ['Visit a clinic within 2–3 days if symptoms continue.', 'Tembelea kliniki ndani ya siku 2–3 ikiwa dalili zinaendelea.'],
            'low' => ['Rest at home, drink fluids, and monitor. Seek care if symptoms worsen.', 'Pumzika nyumbani, kunywa maji, na fuatilia. Tafuta huduma ikiwa dalili zinaongezeka.'],
        ];
        [$en, $sw] = $map[$urgency] ?? $map['medium'];
        return $lang === 'sw' ? $sw : $en;
    }

    private function buildExplanation(string $symptoms, array $conditions, string $lang): string
    {
        $names = implode(', ', array_column($conditions, 'name'));

        $ai = $this->callOpenAI(
            $lang === 'sw'
                ? "Dalili: $symptoms. Hali zinazowezekana: $names. Toa maelezo mafupi kwa Kiswahili (chini ya maneno 70). Usitoe utambuzi rasmi."
                : "Symptoms: $symptoms. Possible conditions: $names. Give a short plain explanation (under 70 words). Not a formal diagnosis."
        );

        if ($ai) return $ai;

        return $lang === 'sw'
            ? "Dalili zako zinaweza kuhusiana na: $names. Hii si utambuzi rasmi."
            : "Your symptoms may relate to: $names. This is not a formal diagnosis.";
    }

    private function callOpenAI(string $prompt): ?string
    {
        if (OPENAI_API_KEY === '') return null;

        $payload = json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Rural healthcare information assistant. Short, practical answers. Never diagnose.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 180,
            'temperature' => 0.4,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code >= 400) return null;

        $data = json_decode($response, true);
        return trim($data['choices'][0]['message']['content'] ?? '') ?: null;
    }
}