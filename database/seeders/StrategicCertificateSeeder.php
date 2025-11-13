<?php

// database/seeders/StrategicCertificateSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CertificateAxis;
use App\Models\CertificateQuestion;

class StrategicCertificateSeeder extends Seeder
{
    public function run(): void
    {
        // 🧹 Clean old strategic questions
        CertificateQuestion::where('path', 'strategic')->delete();
        CertificateAxis::where('path', 'strategic')->delete();

        // 🧭 Create Strategic Axis
        $axis = CertificateAxis::create([
            'name' => 'المسار الاستراتيجي',
            'description' => 'محور خاص بالمسار الاستراتيجي',
            'path' => 'strategic',
            'weight' => 1.0,
        ]);

        // 📝 Define Strategic Questions
        $questions = [
            [
                'question_text' => 'ما هو موعد نشر التقرير السنوي للجمعية لهذا العام؟',
                'options' => [
                    'قبل شهر 3',
                    'بعد شهر 3',
                    'بعد شهر 5',
                    'بعد شهر 6',
                    'بعد شهر 7',
                    'بعد شهر 8',
                    'بعد شهر 9',
                    'بعد شهر 10',
                ],
                'points_mapping' => [
                    'قبل شهر 3' => 15,
                    'بعد شهر 3' => 10,
                    'بعد شهر 5' => 8,
                    'بعد شهر 6' => 6,
                    'بعد شهر 7' => 5,
                    'بعد شهر 8' => 4,
                    'بعد شهر 9' => 3,
                    'بعد شهر 10' => 2,
                ],
                'attachment_required' => true,
                'weight' => 1.0,
            ],
            [
                'question_text' => 'كم كانت درجة آخر تقييم للحوكمة الصادر للجمعية؟',
                'options' => [
                    'أقل من 65',
                    'من 65 - 75',
                    'من 76 - 85',
                    'من 86 - 100',
                ],
                'points_mapping' => [
                    'أقل من 65' => 0,
                    'من 65 - 75' => 15,
                    'من 76 - 85' => 30,
                    'من 86 - 100' => 50,
                ],
                'attachment_required' => true,
                'weight' => 1.0,
            ],
            [
                'question_text' => 'كم كانت درجة تقييم الأداء الاستراتيجي في نهاية العام؟',
                'options' => [
                    'أقل من 65%',
                    'من 65 - 75%',
                    'من 76 - 85%',
                    'من 86 - 100%',
                ],
                'points_mapping' => [
                    'أقل من 65%' => 0,
                    'من 65 - 75%' => 15,
                    'من 76 - 85%' => 30,
                    'من 86 - 100%' => 45,
                ],
                'attachment_required' => true,
                'weight' => 1.0,
            ],
            [
                'question_text' => 'تقرير الاستدامة: ما حالة التقرير في الجمعية حاليًا؟',
                'options' => [
                    'لن يعد',
                    'جاري الإعداد',
                    'تم الانتهاء ولم يُنشر',
                    'تم النشر',
                ],
                'points_mapping' => [
                    'لن يعد' => 0,
                    'جاري الإعداد' => 5,
                    'تم الانتهاء ولم يُنشر' => 10,
                    'تم النشر' => 15,
                ],
                'attachment_required' => true,
                'weight' => 1.0,
            ],
            [
                'question_text' => 'تقرير قياس الأثر: ما حالة التقرير في الجمعية حاليًا؟',
                'options' => [
                    'لن يعد',
                    'جاري الإعداد',
                    'تم الانتهاء ولم يُنشر',
                    'تم النشر',
                ],
                'points_mapping' => [
                    'لن يعد' => 0,
                    'جاري الإعداد' => 2.5,
                    'تم الانتهاء ولم يُنشر' => 7,
                    'تم النشر' => 10,
                ],
                'attachment_required' => true,
                'weight' => 1.0,
            ],
        ];

        // 💾 Insert Questions
        foreach ($questions as $q) {
            CertificateQuestion::create([
                'certificate_axis_id' => $axis->id,
                'question_text' => $q['question_text'],
                'options' => $q['options'],
                'points_mapping' => $q['points_mapping'],
                'attachment_required' => $q['attachment_required'],
                'path' => 'strategic',
                'weight' => $q['weight'],
            ]);
        }

        $this->command->info('✅ Strategic certificate questions seeded successfully!');
    }
}