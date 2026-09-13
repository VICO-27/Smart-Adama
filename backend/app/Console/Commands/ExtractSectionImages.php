<?php

namespace App\Console\Commands;

use App\Models\Section;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExtractSectionImages extends Command
{
    protected $signature = 'books:extract-section-images';
    protected $description = 'Extract embedded base64 images from section raw_text into static storage files.';

    public function handle(): int
    {
        $storageDir = storage_path('app/public/sections');
        if (!File::isDirectory($storageDir)) {
            File::makeDirectory($storageDir, 0755, true, true);
        }

        $sections = Section::where('raw_text', 'like', '%data:image%')->get();
        $this->info("Found {$sections->count()} section(s) with embedded base64 images.");

        $totalImagesExtracted = 0;
        $totalBytesSaved = 0;

        foreach ($sections as $section) {
            $initialLength = strlen($section->raw_text);
            $idx = 0;

            $updatedText = preg_replace_callback(
                '/(src=["\'])(data:image\/([a-zA-Z0-9]+);base64,([^"\']+))(["\'])/i',
                function ($matches) use ($section, $storageDir, &$idx, &$totalImagesExtracted) {
                    $prefix = $matches[1];
                    $extension = strtolower($matches[3]) === 'jpeg' ? 'jpg' : strtolower($matches[3]);
                    $base64Data = $matches[4];
                    $suffix = $matches[5];

                    $decodedData = base64_decode($base64Data);
                    if (!$decodedData) {
                        return $matches[0]; // fallback if decoding fails
                    }

                    $filename = "sec_{$section->id}_{$idx}.{$extension}";
                    $filePath = "{$storageDir}/{$filename}";

                    File::put($filePath, $decodedData);
                    $idx++;
                    $totalImagesExtracted++;

                    $publicUrl = "/storage/sections/{$filename}";
                    return "{$prefix}{$publicUrl}{$suffix}";
                },
                $section->raw_text
            );

            // Also check for Markdown image syntax: ![alt](data:image/...)
            $updatedText = preg_replace_callback(
                '/(!\[[^\]]*\]\()(data:image\/([a-zA-Z0-9]+);base64,([^\)]+))(\))/i',
                function ($matches) use ($section, $storageDir, &$idx, &$totalImagesExtracted) {
                    $prefix = $matches[1];
                    $extension = strtolower($matches[3]) === 'jpeg' ? 'jpg' : strtolower($matches[3]);
                    $base64Data = $matches[4];
                    $suffix = $matches[5];

                    $decodedData = base64_decode($base64Data);
                    if (!$decodedData) {
                        return $matches[0];
                    }

                    $filename = "sec_{$section->id}_{$idx}.{$extension}";
                    $filePath = "{$storageDir}/{$filename}";

                    File::put($filePath, $decodedData);
                    $idx++;
                    $totalImagesExtracted++;

                    $publicUrl = "/storage/sections/{$filename}";
                    return "{$prefix}{$publicUrl}{$suffix}";
                },
                $updatedText
            );

            $finalLength = strlen($updatedText);
            $bytesSaved = $initialLength - $finalLength;
            $totalBytesSaved += $bytesSaved;

            $section->raw_text = $updatedText;
            $section->save();

            $kbSaved = round($bytesSaved / 1024, 1);
            $this->line(" → Section {$section->id} ({$section->title}): extracted {$idx} image(s), saved {$kbSaved} KB");
        }

        $mbSaved = round($totalBytesSaved / (1024 * 1024), 2);
        $this->info("\nCompleted! Total images extracted: {$totalImagesExtracted}. Total size reduced: {$mbSaved} MB.");

        return 0;
    }
}
