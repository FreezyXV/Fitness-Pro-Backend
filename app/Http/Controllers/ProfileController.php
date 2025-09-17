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

            // Map frontend field names to backend field names
            $fieldMapping = [
                'dateOfBirth' => 'date_of_birth',
                'bloodGroup' => 'blood_group',
                'profilePhotoUrl' => 'profile_photo_url',
                // Add birth_date as alias for date_of_birth
                'birthDate' => 'birth_date',
                'bloodType' => 'blood_type'
            ];

            $requestData = $request->all();
            foreach ($fieldMapping as $frontendField => $backendField) {
                if (isset($requestData[$frontendField])) {
                    $requestData[$backendField] = $requestData[$frontendField];
                    // Keep both for compatibility
                }
            }

            // Create new request with mapped data
            $request->merge($requestData);

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
                'bio' => 'sometimes|nullable|string|max:1000',
                'blood_type' => 'sometimes|nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'blood_group' => 'sometimes|nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'location' => 'sometimes|nullable|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'birth_date' => 'sometimes|nullable|date|before:today',
                'date_of_birth' => 'sometimes|nullable|date|before:today',
                'avatar' => 'sometimes|nullable|string|max:1048576', // Allow larger for base64
                'profile_photo_url' => 'sometimes|nullable|string|max:1048576',
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
                'bio.max' => 'La biographie ne peut pas dépasser 1000 caractères',
                'blood_type.in' => 'Le groupe sanguin doit être valide',
                'blood_group.in' => 'Le groupe sanguin doit être valide',
                'location.max' => 'La localisation ne peut pas dépasser 255 caractères',
                'phone.max' => 'Le téléphone ne peut pas dépasser 20 caractères',
                'birth_date.date' => 'La date de naissance doit être une date valide',
                'date_of_birth.date' => 'La date de naissance doit être une date valide',
                'birth_date.before' => 'La date de naissance doit être antérieure à aujourd\'hui',
                'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui',
                'avatar.url' => 'L\'URL de l\'avatar doit être valide',
                'avatar.max' => 'L\'URL de l\'avatar ne peut pas dépasser 2048 caractères',
                'profile_photo_url.url' => 'L\'URL de la photo de profil doit être valide',
                'profile_photo_url.max' => 'L\'URL de la photo de profil ne peut pas dépasser 2048 caractères',
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
                    } elseif (in_array($key, ['avatar', 'profile_photo_url'])) {
                        // Handle avatar/photo data - can be URL or base64
                        $photoValue = trim($value);
                        if (str_starts_with($photoValue, 'data:image/')) {
                            // Already a data URL, use as is
                            $updateData[$key] = $photoValue;
                        } elseif (str_starts_with($photoValue, 'http')) {
                            // It's a URL, use as is
                            $updateData[$key] = $photoValue;
                        } elseif (base64_decode($photoValue, true) !== false && strlen($photoValue) > 100) {
                            // Looks like base64 data, convert to data URL
                            $updateData[$key] = 'data:image/png;base64,' . $photoValue;
                        } else {
                            // Just use the value as provided
                            $updateData[$key] = $photoValue;
                        }

                        // If updating avatar, also update profile_photo_url and vice versa
                        if ($key === 'avatar') {
                            $updateData['profile_photo_url'] = $updateData[$key];
                        } elseif ($key === 'profile_photo_url') {
                            $updateData['avatar'] = $updateData[$key];
                        }
                    } else {
                        $updateData[$key] = trim($value);
                    }
                } elseif (in_array($key, ['age', 'height', 'weight', 'gender', 'activity_level', 'location', 'bio', 'blood_type', 'blood_group', 'phone', 'birth_date', 'date_of_birth', 'avatar', 'profile_photo_url'])) {
                    // Allow null for optional fields
                    $updateData[$key] = null;
                }
            }

            Log::info('Profile update data prepared', [
                'user_id' => $user->id,
                'update_data' => $updateData,
                'original_request' => $request->all()
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
            // Check payload size before processing
            $contentLength = $request->header('Content-Length');
            if ($contentLength && $contentLength > 20971520) { // 20MB
                return $this->errorResponse('Fichier trop volumineux. Taille maximum: 20MB', 413);
            }

            $authCheck = $this->requireAuth();
            if ($authCheck) return $authCheck;

            $user = $this->getAuthenticatedUser();

            Log::info('Profile photo update request', [
                'user_id' => $user->id,
                'content_type' => $request->header('Content-Type'),
                'content_length' => $request->header('Content-Length'),
                'has_photo_url' => $request->has('photo_url'),
                'has_avatar' => $request->has('avatar'),
                'has_photo' => $request->has('photo'),
                'has_file' => $request->hasFile('photo'),
                'has_file_avatar' => $request->hasFile('avatar'),
                'photo_size' => $request->has('photo') ? strlen($request->photo) : null,
                'avatar_size' => $request->has('avatar') ? strlen($request->avatar) : null,
                'request_size' => strlen(json_encode($request->all()))
            ]);

            // Validation rules for photo upload
            $rules = [
                'photo_url' => 'sometimes|nullable|url|max:2048',
                'avatar' => 'sometimes|nullable|string|max:1048576', // Allow URLs or base64
                'photo' => 'sometimes|nullable', // Can be file or string
            ];

            // If photo is a file, add file validation
            if ($request->hasFile('photo')) {
                $rules['photo'] = 'sometimes|file|mimes:jpeg,jpg,png,gif,webp|max:5120'; // 5MB max
            } elseif ($request->has('photo') && is_string($request->photo)) {
                $rules['photo'] = 'sometimes|string|max:1048576'; // 1MB for base64
            }

            // If avatar is a file, add file validation
            if ($request->hasFile('avatar')) {
                $rules['avatar'] = 'sometimes|file|mimes:jpeg,jpg,png,gif,webp|max:5120'; // 5MB max
            }

            $messages = [
                'photo_url.url' => 'L\'URL de la photo doit être valide',
                'photo_url.max' => 'L\'URL de la photo ne peut pas dépasser 2048 caractères',
                'avatar.url' => 'L\'URL de l\'avatar doit être valide',
                'avatar.max' => 'L\'URL de l\'avatar ne peut pas dépasser 2048 caractères',
                'photo.file' => 'Le fichier photo doit être un fichier valide',
                'photo.mimes' => 'La photo doit être au format: JPEG, JPG, PNG, GIF ou WebP',
                'photo.max' => 'La photo ne peut pas dépasser 5MB',
                'avatar.mimes' => 'L\'avatar doit être au format: JPEG, JPG, PNG, GIF ou WebP',
                'avatar.max' => 'L\'avatar ne peut pas dépasser 5MB',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                Log::warning('Photo update validation failed', [
                    'user_id' => $user->id,
                    'errors' => $validator->errors()->toArray()
                ]);

                return $this->validationErrorResponse($validator->errors(), 'Erreurs de validation de la photo');
            }

            $updateData = [];

            // Handle photo URL
            if ($request->has('photo_url') && $request->photo_url) {
                $updateData['avatar'] = $request->photo_url;
                $updateData['profile_photo_url'] = $request->photo_url;
            }

            // Handle avatar URL (alternative field name)
            if ($request->has('avatar') && $request->avatar) {
                $updateData['avatar'] = $request->avatar;
                $updateData['profile_photo_url'] = $request->avatar;
            }

            // Handle file upload
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                if ($file && $file->isValid()) {
                    // Convert uploaded file to base64 data URL
                    $fileContents = file_get_contents($file->getPathname());
                    $mimeType = $file->getMimeType();
                    $base64 = base64_encode($fileContents);
                    $dataUrl = "data:{$mimeType};base64,{$base64}";

                    $updateData['avatar'] = $dataUrl;
                    $updateData['profile_photo_url'] = $dataUrl;

                    Log::info('File upload processed', [
                        'user_id' => $user->id,
                        'file_size' => $file->getSize(),
                        'mime_type' => $mimeType,
                        'original_name' => $file->getClientOriginalName()
                    ]);
                }
            }
            // Handle file upload for avatar field
            elseif ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                if ($file && $file->isValid()) {
                    // Convert uploaded file to base64 data URL
                    $fileContents = file_get_contents($file->getPathname());
                    $mimeType = $file->getMimeType();
                    $base64 = base64_encode($fileContents);
                    $dataUrl = "data:{$mimeType};base64,{$base64}";

                    $updateData['avatar'] = $dataUrl;
                    $updateData['profile_photo_url'] = $dataUrl;

                    Log::info('Avatar file upload processed', [
                        'user_id' => $user->id,
                        'file_size' => $file->getSize(),
                        'mime_type' => $mimeType,
                        'original_name' => $file->getClientOriginalName()
                    ]);
                }
            }
            // Handle base64 photo data (convert to data URL)
            elseif ($request->has('photo') && $request->photo && is_string($request->photo)) {
                // If it's already a data URL, use it as is
                if (str_starts_with($request->photo, 'data:image/')) {
                    $updateData['avatar'] = $request->photo;
                    $updateData['profile_photo_url'] = $request->photo;
                } elseif (str_starts_with($request->photo, 'http')) {
                    // If it's a URL, use it as is
                    $updateData['avatar'] = $request->photo;
                    $updateData['profile_photo_url'] = $request->photo;
                } else {
                    // If it looks like base64 data, convert to data URL
                    if (base64_decode($request->photo, true) !== false) {
                        $updateData['avatar'] = 'data:image/png;base64,' . $request->photo;
                        $updateData['profile_photo_url'] = 'data:image/png;base64,' . $request->photo;
                    }
                }
            }

            if (empty($updateData)) {
                return $this->errorResponse('Aucune photo fournie. Veuillez fournir une URL d\'image ou des données d\'image.', 400);
            }

            // Update user photo
            $user->update($updateData);

            // Clear user cache after update
            $user->clearCache();

            // Reload user with fresh data
            $updatedUser = $user->fresh();

            Log::info('Profile photo updated successfully', [
                'user_id' => $user->id,
                'avatar_set' => !empty($updateData['avatar']),
                'profile_photo_url_set' => !empty($updateData['profile_photo_url'])
            ]);

            return $this->successResponse([
                'avatar' => $updatedUser->avatar,
                'profile_photo_url' => $updatedUser->profile_photo_url,
                'user' => $updatedUser->toArray()
            ], 'Photo de profil mise à jour avec succès');

        } catch (\Exception $e) {
            Log::error('Profile photo update exception', [
                'user_id' => $this->getUserId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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