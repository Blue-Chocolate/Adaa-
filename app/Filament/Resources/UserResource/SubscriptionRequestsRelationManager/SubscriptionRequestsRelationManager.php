<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Subscription;
use Filament\Notifications\Notification;
use Exception;

class SubscriptionRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptionRequests';
    
    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan Requested')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('User Name')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                    
                Tables\Columns\ImageColumn::make('receipt_image')
                    ->label('Receipt')
                    ->square()
                    ->size(50),
                    
                Tables\Columns\IconColumn::make('is_processed')
                    ->label('Processed')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_processed')
                    ->label('Status')
                    ->placeholder('All requests')
                    ->trueLabel('Processed')
                    ->falseLabel('Pending'),
            ])
            ->headerActions([
                // You can add header actions here if needed
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Subscription Request')
                    ->modalDescription('Are you sure you want to approve this subscription request and create an active subscription for this user?')
                    ->modalSubmitActionLabel('Yes, Approve')
                    ->visible(fn ($record) => !$record->is_processed)
                    ->action(function ($record) {
                        try {
                            // Create subscription
                            Subscription::create([
                                'user_id' => $record->user_id,
                                'plan_id' => $record->plan_id,
                                'starts_at' => now(),
                                'ends_at' => now()->addDays($record->plan->duration),
                                'is_active' => true,
                            ]);

                            // Mark request as processed
                            $record->update(['is_processed' => true]);

                            Notification::make()
                                ->success()
                                ->title('Subscription Created')
                                ->body('User has been subscribed successfully to ' . $record->plan->name)
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Failed to create subscription: ' . $e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
