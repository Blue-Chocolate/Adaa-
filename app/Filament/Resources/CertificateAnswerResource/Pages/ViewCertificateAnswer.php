<?php 

namespace App\Filament\Resources\CertificateAnswerResource\Pages;

use App\Filament\Resources\CertificateAnswerResource;
use App\Models\CertificateAnswer;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewCertificateAnswer extends ViewRecord
{
    protected static string $resource = CertificateAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Removed edit/delete as we're viewing aggregated data
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get all answers for this organization
        $organizationId = $this->record->organization_id;
        $answers = CertificateAnswer::with(['question.axis', 'organization'])
            ->where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get();

        $organization = $answers->first()?->organization;

        // Group answers by axis
        $answersByAxis = $answers->groupBy('question.axis.name');

        $sections = [
            Components\Section::make('Organization Summary')
                ->schema([
                    Components\TextEntry::make('organization.name')
                        ->label('Organization Name'),
                    Components\TextEntry::make('total_questions')
                        ->label('Total Questions Answered')
                        ->state($answers->count()),
                    Components\TextEntry::make('total_points')
                        ->label('Total Points')
                        ->state($answers->sum('points')),
                    Components\TextEntry::make('total_final_points')
                        ->label('Total Final Points')
                        ->state($answers->sum('final_points'))
                        ->color('success'),
                ])
                ->columns(4),
        ];

        // Create a section for each axis
        foreach ($answersByAxis as $axisName => $axisAnswers) {
            $answersSchema = [];
            
            $answerCount = 0;
            $totalAnswers = $axisAnswers->count();
            
            foreach ($axisAnswers as $answer) {
                $answerCount++;
                
                // Question
                $answersSchema[] = Components\TextEntry::make('question_' . $answer->id)
                    ->label('Question')
                    ->state($answer->question->question_text)
                    ->columnSpan('full');
                
                // Answer, Points, Final Points
                $answersSchema[] = Components\Grid::make(3)
                    ->schema([
                        Components\TextEntry::make('answer_' . $answer->id)
                            ->label('Selected Answer')
                            ->state($answer->selected_option)
                            ->badge()
                            ->color('primary'),
                        
                        Components\TextEntry::make('points_' . $answer->id)
                            ->label('Points')
                            ->state($answer->points),
                        
                        Components\TextEntry::make('final_points_' . $answer->id)
                            ->label('Final Points')
                            ->state($answer->final_points)
                            ->color('success'),
                    ]);

                // Attachment and Submission Date
                $answersSchema[] = Components\Grid::make(2)
                    ->schema([
                        Components\TextEntry::make('attachment_' . $answer->id)
                            ->label('Attachment')
                            ->state(function () use ($answer) {
                                if ($answer->attachment_path) {
                                    return '📎 File attached';
                                } elseif ($answer->attchment_url) {
                                    return '🔗 URL provided';
                                }
                                return 'No attachment';
                            }),
                        
                        Components\TextEntry::make('submitted_' . $answer->id)
                            ->label('Submitted At')
                            ->state($answer->created_at->format('M d, Y H:i')),
                    ]);
                
                // Add spacing between answers (but not after the last one)
                if ($answerCount < $totalAnswers) {
                    $answersSchema[] = Components\Section::make()
                        ->schema([])
                        ->columnSpan('full');
                }
            }

            $sections[] = Components\Section::make($axisName ?: 'Uncategorized')
                ->schema($answersSchema)
                ->collapsible()
                ->collapsed(false)
                ->description('Total: ' . $axisAnswers->sum('final_points') . ' points (' . $axisAnswers->count() . ' questions)');
        }

        return $infolist->schema($sections);
    }
}