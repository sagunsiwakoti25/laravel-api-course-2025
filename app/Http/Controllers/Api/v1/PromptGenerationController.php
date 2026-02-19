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
   

    public function index()
    {
        $user = request()->user();
        $imageGenerations = $user->imageGenerations()->latest()->paginate(10);

        return ImageGenerationResource::collection($imageGenerations);
    }

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
