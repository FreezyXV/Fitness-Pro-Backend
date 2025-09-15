<?php
// app/Http/Controllers/ProfileController.php - FULLY FIXED VERSION
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Services\StatisticsService;

class ProfileController extends BaseController
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }
    public function show()
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();
            
            $userData = $user->toArray();
            
            // Get stats using the safe method instead of deprecated properties
            try {
                $stats = $user->getStats();
                $userData['stats'] = $stats;
            } catch (\Exception $e) {
                Log::warning('Stats calculation failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $userData['stats'] = $this->getDefaultStats($user);
            }

            // Get BMI info safely
            try {
                $bmiInfo = $user->getBmiInfo();
                $userData['bmi'] = $bmiInfo['bmi'];
                $userData['bmi_status'] = $bmiInfo['status'];
            } catch (\Exception $e) {
                Log::warning('BMI calculation failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $userData['bmi'] = null;
                $userData['bmi_status'] = 'unknown';
            }

            return $this->successResponse($userData, 'Profile loaded successfully');

        } catch (\Exception $e) {
            Log::error('Profile show exception', [
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleException($e, 'Profile loading');
        }
    }

    public function update(Request $request)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();


            // Enhanced validation rules
            $rules = [
                'name' => 'sometimes|string|min:2|max:255',
                'email' => [
                    'sometimes',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id)
                ],
                'age' => 'sometimes|nullable|integer|min:13|max:120',
                'height' => 'sometimes|nullable|numeric|min:100|max:300',
                'weight' => 'sometimes|nullable|numeric|min:20|max:500',
                'gender' => 'sometimes|nullable|in:male,female,other',
                'activity_level' => 'sometimes|nullable|in:sedentary,lightly_active,moderately_active,very_active,extremely_active',
                'location' => 'sometimes|nullable|string|max:255',
                'bio' => 'sometimes|nullable|string|max:1000',
                'blood_group' => 'sometimes|nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'phone' => 'sometimes|nullable|string|max:20',
                'date_of_birth' => 'sometimes|nullable|date|before:today',
            ];

            // French error messages
            $messages = [
                'name.required' => 'Le nom est obligatoire',
                'name.min' => 'Le nom doit contenir au moins 2 caractères',
                'name.max' => 'Le nom ne peut pas dépasser 255 caractères',
                'email.required' => 'L\'email est obligatoire',
                'email.email' => 'Veuillez entrer un email valide',
                'email.unique' => 'Cet email est déjà utilisé',
                'age.integer' => 'L\'âge doit être un nombre entier',
                'age.min' => 'L\'âge minimum est 13 ans',
                'age.max' => 'L\'âge maximum est 120 ans',
                'height.numeric' => 'La taille doit être un nombre',
                'height.min' => 'La taille minimum est 100 cm',
                'height.max' => 'La taille maximum est 300 cm',
                'weight.numeric' => 'Le poids doit être un nombre',
                'weight.min' => 'Le poids minimum est 20 kg',
                'weight.max' => 'Le poids maximum est 500 kg',
                'gender.in' => 'Le genre doit être: male, female ou other',
                'activity_level.in' => 'Le niveau d\'activité doit être: sedentary, lightly_active, moderately_active, very_active ou extremely_active',
                'location.max' => 'La localisation ne peut pas dépasser 255 caractères',
                'bio.max' => 'La biographie ne peut pas dépasser 1000 caractères',
                'blood_group.in' => 'Le groupe sanguin doit être valide',
                'phone.max' => 'Le téléphone ne peut pas dépasser 20 caractères',
                'date_of_birth.date' => 'La date de naissance doit être une date valide',
                'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                Log::warning('Profile update validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray()
                ]);

                return $this->validationErrorResponse($validator->errors(), 'Erreurs de validation');
            }

            $validated = $validator->validated();

            // Clean and prepare update data
            $updateData = [];
            
            foreach ($validated as $key => $value) {
                if ($value !== null && $value !== '') {
                    if ($key === 'email') {
                        $updateData[$key] = strtolower(trim($value));
                    } elseif ($key === 'name') {
                        $updateData[$key] = trim($value);
                    } elseif (in_array($key, ['age', 'height', 'weight'])) {
                        $updateData[$key] = is_numeric($value) ? (float) $value : null;
                    } else {
                        $updateData[$key] = trim($value);
                    }
                } elseif (in_array($key, ['age', 'height', 'weight', 'gender', 'activity_level', 'location', 'bio', 'blood_group', 'phone', 'date_of_birth'])) {
                    // Allow null for optional fields
                    $updateData[$key] = null;
                }
            }

            Log::info('Profile update data prepared', [
                'user_id' => $user->id,
                'update_data' => array_keys($updateData)
            ]);

            // Update user
            $user->update($updateData);

            // Clear user cache after update
            $user->clearCache();
            
            // Clear statistics cache after profile update
            $this->statisticsService->clearUserCache($user);

            // Reload user with fresh data
            $updatedUser = $user->fresh();

            // Get fresh stats
            try {
                $stats = $updatedUser->getStats();
                $userData = $updatedUser->toArray();
                $userData['stats'] = $stats;
            } catch (\Exception $e) {
                Log::warning('Stats calculation failed after update', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $userData = $updatedUser->toArray();
                $userData['stats'] = [
                    'profile_completion' => $this->calculateProfileCompletion($updatedUser),
                ];
            }

            Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData)
            ]);

            return $this->successResponse($userData, 'Profil mis à jour avec succès');

        } catch (\Exception $e) {
            Log::error('Profile update exception', [
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->handleException($e, 'Mise à jour du profil');
        }
    }

    public function updatePhoto(Request $request)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();

            Log::info('Profile photo update request', ['user_id' => $user->id]);

            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            ], [
                'photo.required' => 'Veuillez sélectionner une photo',
                'photo.image' => 'Le fichier doit être une image',
                'photo.mimes' => 'Les formats autorisés sont: JPEG, PNG, JPG, GIF, WEBP',
                'photo.max' => 'La taille maximum est 10MB'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors(), 'Erreur de validation de l\'image');
            }

            $photo = $request->file('photo');
            
            // Delete old photo if exists
            if ($user->profile_photo_url) {
                $oldPhotoPath = str_replace(url('/storage/'), '', $user->profile_photo_url);
                if (Storage::disk('public')->exists($oldPhotoPath)) {
                    Storage::disk('public')->delete($oldPhotoPath);
                }
            }

            // Create directory if it doesn't exist
            if (!Storage::disk('public')->exists('profile-photos')) {
                Storage::disk('public')->makeDirectory('profile-photos');
            }

            // Store the file with a unique name
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('profile-photos', $filename, 'public');
            $photoUrl = url('/storage/profile-photos/' . $filename);

            $user->update(['profile_photo_url' => $photoUrl]);

            Log::info('Profile photo updated successfully', [
                'user_id' => $user->id,
                'filename' => $filename
            ]);

            return $this->successResponse([
                'user' => $user->fresh(),
                'profile_photo_url' => $photoUrl,
            ], 'Photo de profil mise à jour avec succès');

        } catch (\Exception $e) {
            Log::error('Profile photo update exception', [
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);

            return $this->handleException($e, 'Mise à jour de la photo de profil');
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();

            Log::info('Password change request', ['user_id' => $user->id]);

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string'
            ], [
                'current_password.required' => 'Le mot de passe actuel est obligatoire',
                'new_password.required' => 'Le nouveau mot de passe est obligatoire',
                'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères',
                'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
                'new_password_confirmation.required' => 'La confirmation du mot de passe est obligatoire'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors(), 'Erreurs de validation du mot de passe');
            }

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Le mot de passe actuel est incorrect', 400);
            }

            // Check that new password is different
            if (Hash::check($request->new_password, $user->password)) {
                return $this->errorResponse('Le nouveau mot de passe doit être différent de l\'actuel', 400);
            }

            $user->update(['password' => Hash::make($request->new_password)]);

            Log::info('Password changed successfully', ['user_id' => $user->id]);

            return $this->successResponse(null, 'Mot de passe modifié avec succès');

        } catch (\Exception $e) {
            Log::error('Password change exception', [
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage()
            ]);

            return $this->handleException($e, 'Modification du mot de passe');
        }
    }

    /**
     * Get default stats when calculation fails
     */
    private function getDefaultStats($user): array
    {
        return [
            'total_workouts' => 0,
            'total_minutes' => 0,
            'total_calories' => 0,
            'current_streak' => 0,
            'weekly_workouts' => 0,
            'monthly_workouts' => 0,
            'active_goals' => 0,
            'completed_goals' => 0,
            'has_completed_today' => false,
            'profile_completion' => $this->calculateProfileCompletion($user),
        ];
    }

    /**
     * Calculate profile completion percentage safely
     */
    private function calculateProfileCompletion($user): int
    {
        try {
            $fields = ['name', 'email', 'age', 'height', 'weight', 'gender', 'location', 'bio'];
            $completed = 0;
            $total = count($fields);
            
            foreach ($fields as $field) {
                if (!empty($user->$field)) {
                    $completed++;
                }
            }
            
            return $total > 0 ? round(($completed / $total) * 100) : 0;
        } catch (\Exception $e) {
            Log::warning('Profile completion calculation failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}