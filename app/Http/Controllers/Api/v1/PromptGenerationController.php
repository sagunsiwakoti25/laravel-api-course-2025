<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\GeneratePromptRequest;
use App\Http\Resources\ImageGenerationResource;
use App\Http\Controllers\Controller;
use App\Services\OpenAiService;
use Illuminate\Http\Request;

class PromptGenerationController extends Controller
{
    //

    public function __construct(private OpenAiService $openAiService)
    {

    }
   
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->imageGenerations();

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where('generated_prompt', 'LIKE', '%' . $request->search . '%');
        }

        // Apply sorting
        $allowedSortFields = ['created_at', 'generated_prompt', 'original_filename', 'file_size'];
        $sortField = 'created_at';
        $sortDirection = 'desc';

        if ($request->has('sort') && !empty($request->sort)) {
            $sort = $request->sort;
            if (str_starts_with($sort, '-')) {
                $sortField = substr($sort, 1);
                $sortDirection = 'desc';
            } else {
                $sortField = $sort;
                $sortDirection = 'asc';
            }
        }

        // Validate sort field
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
            $sortDirection = 'desc';
        }

        $query->orderBy($sortField, $sortDirection);

        $imageGenerations = $query->paginate($request->get('per_page'));
        return ImageGenerationResource::collection($imageGenerations);
    }

    /**
     * Generate a prompt from the uploaded image and store the result.
     * @param GeneratePromptRequest $request
     * @return ImageGenerationResource
     * Handle the incoming request.
     */
    public function store(GeneratePromptRequest $request)
    {
        $user = request()->user();
        $image = $request->file('image');

        $originalName = $image->getClientOriginalName();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $extension = $image->getClientOriginalExtension();
        $safeFileName = $sanitizedName . '_' . time() . '.' . $extension;

        $imagePath = $image->storeAs('uploads/images', $safeFileName, 'public');
        $generatedPrompt = $this->openAiService->generatePromptFromImage($image);

        $imageGeneration = $user->imageGenerations()->create([
            'generated_prompt' => $this->openAiService->generatePromptFromImage($image),
            'image_path' => $imagePath,
            'original_filename' => $originalName,
            'file_size' => $image->getSize(),
            'mime_type' => $image->getMimeType(),
        ]);

        return new ImageGenerationResource($imageGeneration);
    }
    
}
