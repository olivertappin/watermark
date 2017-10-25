<?php

/**
 * Class CoverWatermark
 */
class CoverWatermark extends Watermark
{
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
        $image = imagecreatefromjpeg($file);

        $width = imagesx($image);
        $height = imagesy($image);

        $xStep = intval($width / $numberOfSamples);
        $yStep = intval($height / $numberOfSamples);

        $totalLuminance = 0;
        $sample_no = 1;

        for ($x = 0; $x < $width; $x += $xStep) {
            for ($y = 0; $y < $height; $y += $yStep) {
                $rgb = imagecolorat($image, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;

                // Choose a simple luminance formula from here
                // http://stackoverflow.com/questions/596216/formula-to-determine-brightness-of-rgb-color
                $lum = ($red + $red + $blue + $green + $green + $green) / 6;

                $totalLuminance += $lum;

                // Debugging code
                // echo "$sample_no - XY: $x,$y = $r, $g, $b = $lum<br />";
                $sample_no++;
            }
        }

        // Return the average luminance
        return $totalLuminance / $sample_no;
    }

    /**
     * Adds a watermark covering the photo from top to bottom using a PNG and
     * saving the image in a JPG format whilst preserving the original and
     * saving to an export directory.
     *
     * @param string $inputFile
     * @param string $outputFile
     * @return bool
     */
    public function addWatermarkToImage($inputFile, $outputFile)
    {
        // Fetch the covering watermark pattern
        $watermark = imagecreatefrompng($this->getSuitableLogo($inputFile));
        $image = imagecreatefromjpeg($inputFile);

        // Copy the stamp image onto our photo using the margin offsets and the photo
        // width to calculate positioning of the stamp.
        imagecopy($image, $watermark, 0, 0, 0, 0, imagesx($watermark), imagesy($watermark));

        // Output and free memory
        $created = imagejpeg($image, $outputFile, 100);

        // Free up memory
        imagedestroy($image);

        // Return true if success, false if failed
        return $created;
    }
}
