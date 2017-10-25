<?php

/**
 * Class Watermark
 */
class Watermark
{
    /**
     * @var int
     */
    protected $luminance = 170;

    /**
     * @var int
     */
    protected $margin = 50;

    /**
     * @var int
     */
    protected $maxWidth = 1500;

    /**
     * @var int
     */
    protected $maxHeight = 1500;

    /**
     * @var string
     */
    protected $lightImage = 'logos/light.png';

    /**
     * @var string
     */
    protected $darkImage = 'logos/dark.png';

    /**
     * @var string
     */
    protected $inputDirectory = 'input';

    /**
     * @var string
     */
    protected $outputDirectory = 'output';

    /**
     * Get the luminance
     *
     * @return int
     */
    public function getLuminance()
    {
        return $this->luminance;
    }

    /**
     * Set the luminance
     *
     * @param int $luminance
     */
    public function setLuminance($luminance)
    {
        $this->luminance = (int)$luminance;
    }

    /**
     * Get the margin
     *
     * @return int
     */
    public function getMargin()
    {
        return $this->margin;
    }

    /**
     * Set the margin
     *
     * @param int $margin
     */
    public function setMargin($margin)
    {
        $this->margin = (int)$margin;
    }

    /**
     * Get the maximum width
     *
     * @return int
     */
    public function getMaxWidth()
    {
        return $this->maxWidth;
    }

    /**
     * Set the maximum width
     *
     * @param int $maxWidth
     */
    public function setMaxWidth($maxWidth)
    {
        $this->maxWidth = (int)$maxWidth;
    }

    /**
     * Get the maximum height
     *
     * @return int
     */
    public function getMaxHeight()
    {
        return $this->maxHeight;
    }

    /**
     * Set the maximum height
     *
     * @param int $maxHeight
     */
    public function setMaxHeight($maxHeight)
    {
        $this->maxHeight = (int)$maxHeight;
    }

    /**
     * Set the light image
     *
     * @param string $lightImage
     */
    public function setLightImage($lightImage)
    {
        $this->lightImage = $lightImage;
    }

    /**
     * Get the light image
     *
     * @return string
     */
    public function getLightImage()
    {
        return $this->lightImage;
    }

    /**
     * Set the dark image
     *
     * @param string $darkImage
     */
    public function setDarkImage($darkImage)
    {
        $this->darkImage = $darkImage;
    }

    /**
     * Get the dark image
     *
     * @return string
     */
    public function getDarkImage()
    {
        return $this->darkImage;
    }

    /**
     * Set the input directory
     *
     * If the directory provided is absolute, we will use the exact value. If
     * the directory is relative, it will be relative to the project path.
     *
     * @param string $inputDirectory
     */
    public function setInputDirectory($inputDirectory)
    {
        $this->inputDirectory = rtrim($inputDirectory, '/');
    }

    /**
     * Get the input directory
     *
     * @return string
     */
    public function getInputDirectory()
    {
        return $this->inputDirectory;
    }

    /**
     * Set the output directory
     *
     * If the directory provided is absolute, we will use the exact value. If
     * the directory is relative, it will be relative to the project path.
     *
     * @param string $outputDirectory
     */
    public function setOutputDirectory($outputDirectory)
    {
        $this->outputDirectory = rtrim($outputDirectory, '/');
    }

    /**
     * Get the output directory
     *
     * @return string
     */
    public function getOutputDirectory()
    {
        return $this->outputDirectory;
    }

    /**
     * Get average luminance, by sampling $num_sample times in both x and y
     * directions.
     *
     * @return int
     * @param string $file The file to check
     * @param int $numberOfSamples Number of samples
     */
    protected function getAverageLuminance($file, $numberOfSamples = 10)
    {
        // Validate both light and dark images are the same height and width

        // Load the watermark
        $watermark = imagecreatefrompng($this->lightImage);
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        // Load the image where the watermark will be applied
        $image = imagecreatefromjpeg($file);
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // Get the right and bottom margin (subject to change to allow flexibility)
        $marginRight = $this->margin;
        $marginBottom = $this->margin;

        // At this point we're assuming the sample is in the bottom right

        // Calculate the sample x and y values against the image
        $sampleX = $imageWidth - $watermarkWidth - ($marginRight * 2);
        $sampleY = $imageHeight - $watermarkHeight - ($marginBottom * 2);

        // Calculate the sample height and width to be analysed for luminosity
        $sampleWidth = $imageWidth - $sampleX;
        $sampleHeight = $imageHeight - $sampleY;

        // Calculate the x and y step values
        $xStep = intval($sampleWidth / $numberOfSamples);
        $yStep = intval($sampleHeight / $numberOfSamples);

        $totalLuminance = 0;
        $sampleNumber = 1;

        for ($x = $sampleX; $x < $imageWidth; $x += $xStep) {
            for ($y = $sampleY; $y < $imageHeight; $y += $yStep) {
                $rgb = imagecolorat($image, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;

                // Choose a simple luminance formula from here
                // http://stackoverflow.com/questions/596216/formula-to-determine-brightness-of-rgb-color
                $lum = ($red + $red + $blue + $green + $green + $green) / 6;

                $totalLuminance += $lum;

                // Debugging code
                //echo "$sampleNumber - XY: $x,$y = $red, $green, $blue = $lum" . PHP_EOL;
                $sampleNumber++;
            }
        }

        // Return the average luminance
        return $totalLuminance / $sampleNumber;
    }

    /**
     * Get a suitable logo depending on the average luminance
     * in the $file provided.
     *
     * @return string
     * @param string $file The path of the image
     */
    protected function getSuitableLogo($file)
    {
        if ($this->luminance >= $this->getAverageLuminance($file)) {
            return $this->lightImage;
        }
        return $this->darkImage;
    }

    /**
     * Adds a single watermarked PNG to a photo in JPG format whilst preserving
     * the original and saving to an export directory.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @return bool
     */
    public function addWatermarkToImage($inputFile, $outputFile)
    {
        // Load the image where the watermark will be applied
        $image = imagecreatefromjpeg($inputFile);
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // Make the image progressive
        imageinterlace($image, true);

        // Use the luminous function to work out which watermark we need
        $watermark = imagecreatefrompng($this->getSuitableLogo($inputFile));
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        // Set the margins for the stamp and get the height/width of the stamp image
        $marginRight = $this->margin;
        $marginBottom = $this->margin;

        // Copy the watermark to the photo using the margin offsets and the photo
        // width to calculate positioning of the watermark
        imagecopy(
            $image,
            $watermark,
            $imageWidth - $watermarkWidth - $marginRight,
            $imageHeight - $watermarkHeight - $marginBottom,
            0,
            0,
            $watermarkWidth,
            $watermarkHeight
        );

        // Create the image
        $created = imagejpeg($image, $outputFile, 100);

        // Free up memory
        imagedestroy($image);

        // Return true if successed, false if failed
        return $created;
    }

    /**
     * @param string $inputFile
     * @param string $outputFile
     * @return bool
     */
    protected function resizeImage($inputFile, $outputFile)
    {
        // Set the maximum height and width
        $width = $this->maxWidth;
        $height = $this->maxHeight;

        // Load the original image
        $image = imagecreatefromjpeg($inputFile);

        // Rotate the image if necessary
        $exif = @exif_read_data($inputFile);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
            }
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        $originalRatio = $originalWidth / $originalHeight;

        if ($width / $height > $originalRatio) {
            $width = $height * $originalRatio;
        } else {
            $height = $width / $originalRatio;
        }

        $resizedImage = imagecreatetruecolor($width, $height);
        imagecopyresampled(
            $resizedImage,
            $image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $originalWidth,
            $originalHeight
        );

        // Create the image and return the raw output
        $created = imagejpeg($resizedImage, $outputFile, 100);

        // Free up memory
        imagedestroy($resizedImage);

        // Return true if success, false if failed
        return $created;
    }

    /**
     * Checks to see if the specified file path is a jpeg
     *
     * @param string $file The file path
     * @return bool
     */
    protected function isJpeg($file)
    {
        return '.jpg' === strtolower(substr($file, -4));
    }

    /**
     * Run the watermark script
     *
     * @return void
     */
    public function run()
    {
        $files = glob($this->getInputDirectory() . '/*');
        $temporaryDirectory = sys_get_temp_dir();

        foreach ($files as $inputFile) {
            echo 'Processing image: ' . $inputFile . ' ... ';

            if (!$this->isJpeg($inputFile)) {
                echo 'Image is not in JPG format, skipping.' . PHP_EOL;
                continue;
            }

            // Create a temporary file for resizing
            $temporaryFile = tempnam($temporaryDirectory, static::class);

            if (!$this->resizeImage($inputFile, $temporaryFile)) {
                echo 'Failed to resize image, skipping.' . PHP_EOL;
                unlink($temporaryFile);
                continue;
            }

            echo 'Image resized ... ';

            // Get the output file
            $fileName = explode('/', $inputFile);
            $fileName = end($fileName);
            $outputFile = $this->outputDirectory . DIRECTORY_SEPARATOR . $fileName;
            unset($fileName);

            if (!$this->addWatermarkToImage($temporaryFile, $outputFile)) {
                echo 'Failed to apply watermark to image, skipping.' . PHP_EOL;
                unlink($temporaryFile);
                continue;
            }

            echo 'Watermark applied to image' . PHP_EOL;

            // Free up memory
            unlink($temporaryFile);
        }

        echo 'Complete.' . PHP_EOL;
    }
}
