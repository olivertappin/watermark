# Watermark

# Apply a watermark to a directory of images

Apply a single watermark to the bottom right corner of an image.

    $watermark = new Watermark();
    $watermark->setLightImage('logos/light.png');
    $watermark->setDarkImage('logos/dark.png');
    $watermark->setInputDirectory('path/to/input');
    $watermark->setOutputDirectory('path/to/output');
    $watermark->run();

Apply a covered watermark across an entire image.

    $watermark = new CoverWatermark();
    $watermark->setLightImage('logos/light-cover.png');
    $watermark->setDarkImage('logos/dark-cover.png');
    $watermark->setInputDirectory('path/to/input');
    $watermark->setOutputDirectory('path/to/output');
    $watermark->run();

The following defaults are already set:

* Light image: `logos/light.png`
* Dark image: `logos/dark.png`
* Input directory: `input`
* Output directory: `output`     
    
You may use the setters to overwrite these values with your own.

At its simplest, you can use the class like this:

    $watermark = new Watermark();
    $watermark->run();
    
So long as your input image is already within the `input` directory and your light and dark images are named correctly as the defaults show above within the `logos` directory, both of which should be relative to the project.