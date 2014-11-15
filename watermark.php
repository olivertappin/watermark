<?php

// Uncomment this line if using large images on a local machine
//ini_set('memory_limit', -1);

class Watermark
{
    const LUMINANCE = 170;
    const MARGIN = 20;

    private $inputDirectory;
    private $outputDirectory;

    /**
     * Set the input directory
     * @author Oliver Tappin <olivertappin@gmail.com>
     * @param string $inputDirectory
     */
    public function setInputDirectory($inputDirectory)
    {
        $this->inputDirectory = $inputDirectory;
    }

    /**
     * Set the output directory
     * @author Oliver Tappin <olivertappin@gmail.com>
     * @param string $inputDirectory
     */
    public function setOutputDirectory($outputDirectory)
    {
        $this->outputDirectory = $outputDirectory;
    }

    /**
     * Get the input directory
     * @author Oliver Tappin <olivertappin@gmail.com>
     * @return string
     */
    public function getInputDirectory()
    {
        return $this->inputDirectory;
    }

    /**
     * Get the output directory
     * @author Oliver Tappin <olivertappin@gmail.com>
     * @return string
     */
    public function getOutputDirectory()
    {
        return $this->outputDirectory;
    }

    /**
     * Get average luminance, by sampling $num_samples
     * times in both x,y directions.
     *
     * @author Oliver Tappin <olivertappin@gmail.com>
     * @return int
     * @param string $filename The file to check
     * @param string $num Number of samples
     */
    private function getAverageLuminance($file, $num_samples = 10) {

        $image = imagecreatefromjpeg($file);

        $width = imagesx($image);
        $height = imagesy($image);

        $x_step = intval($width/$num_samples);
        $y_step = intval($height/$num_samples);

        $total_lum = 0;
        $sample_no = 1;

        for ($x = 0; $x < $width; $x+=$x_step) {
        
            for ($y = 0; $y < $height; $y+=$y_step) {

                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Choose a simple luminance formula from here
                // http://stackoverflow.com/questions/596216/formula-to-determine-brightness-of-rgb-color
                $lum = ($r + $r + $b + $g + $g + $g) / 6;

                $total_lum += $lum;

                // Debugging code
                // echo "$sample_no - XY: $x,$y = $r, $g, $b = $lum<br />";
                $sample_no++;
            }
            
        }

        // Work out the average
        $avg_lum = $total_lum / $sample_no;

        return $avg_lum;
        
    }

    /**
     * Get a suitable logo depending on the average luminance
     * in the $file provided.
     *
     * @author Oliver Tappin <olivertappin@gmail.com>
     * @return string
     * @param string $file The path of the image
     */
    private function getSuitableLogo($file) {
        $luminance = $this->getAverageLuminance($file);
        if ($luminance <= self::LUMINANCE) {
            return 'logos/light.png';
        }
        return 'logos/dark.png';
    }

    /**
     * Adds a watermarked PNG to a photo in JPG format
     * whilst preserving the original and saving to an
     * export directory.
     *
     * @author Oliver Tappin <olivertappin@gmail.com>
     * @return bool
     */
    public function addWatermarkToImage($file) {

        // Use the luminous function to work out which image we need
        $watermark = imagecreatefrompng($this->getSuitableLogo($file));
        $image = imagecreatefromjpeg($file);
        
        // Set the margins for the stamp and get the height/width of the stamp image
        $marge_right = self::MARGIN;
        $marge_bottom = self::MARGIN;
        $sx = imagesx($watermark);
        $sy = imagesy($watermark);
        
        // Copy the watermark to the photo using the margin offsets and the photo
        // Width to calculate positioning of the watermark
        imagecopy($image, $watermark, imagesx($image) - $sx - $marge_right, imagesy($image) - $sy - $marge_bottom, 0, 0, imagesx($watermark), imagesy($watermark));
        
        // Get the filename without the directory
        $file = explode('/', $file);
        $file = end($file);
        
        // Output and free memory
        $created = imagejpeg($image, __DIR__ . DIRECTORY_SEPARATOR . $this->getOutputDirectory() . DIRECTORY_SEPARATOR . $file, 100);
        
        // Free up memory
        imagedestroy($image);
        
        // Return true if successed, false if failed
        return $created;
        
    }

    private function getRamUsage()
    {
        return 'Memory Usage : ' . memory_get_usage(true) / 1024 / 1024 . "MB";
    }

    private function uiMessage($message)
    {
        echo "--> " . $message . PHP_EOL;
    }

    private function isJpeg($file)
    {
        return (substr($file, -4) == '.jpg');
    }

    public function run()
    {
        $inputDirectory = __DIR__ . DIRECTORY_SEPARATOR . $this->getInputDirectory();
        $outputDirectory = __DIR__ . DIRECTORY_SEPARATOR . $this->getOutputDirectory();
        $files = glob($inputDirectory . '/*');

        foreach ($files as $count => $file) {
            if (!$this->isJpeg($file)) { continue; }
            if ($this->addWatermarkToImage($file)) {
                $this->uiMessage("Watermark created for image: " . $file);
            }
        }
    }

}


/**
 * How to use the watermark class
 */
 
// Create a new instance of the Watermark class
$watermark = new Watermark();

// Set the input directory (where the images are located)
$watermark->setInputDirectory('input');

// Set the output directory (where the files will be saved)
$watermark->setOutputDirectory('output');

// Run the script
$watermark->run();
