<?php

namespace App\Traits;

use Carbon\Carbon; //se usa para las fechas
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// use Intervention\Image\Facades\Image;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\Image as ImageModel;
use App\Models\Store;
use Illuminate\Http\Request;

trait UploadImagesTrait
{

    public function uploadStoreOption(Store $store, Request $request)
    {

        $existe = false;

        $options = $store->options;

        // Log::info($getOptions);

        // foreach ($options as $option) { //getOptions de de la base de datos, hemos colocado 'get' a las variables para identificar que son las que vienen de la base de datos
        //     # code...
        //     if ($request->name == $option->name) { //esto quiere decir que $name (de la plantilla) es igual a $option->name (de la base de datos)

        //         $existe = true;
        //         // uploadImage($request, $dir = "", $size = 0, $link = false)
        //         $option->value = $this->uploadImage($request, "options", 0, true); //el value es el valor de la plantilla, 0 quiere decir que no redimencione y true quiere decir que devuelve un link comleto
        //         $option->save();

        //         break;
        //     } else {
        //         $existe = false;
        //     }
        // }

        // Borramos todo excepto los que tengan la palabra "upload"
        foreach ($options as $option) {

            /*
                La razón por la que se usa if ($posicion !== false) en lugar de if ($posicion == true) 
                es porque strpos() devuelve la posición de la subcadena si la encuentra y false si no 
                la encuentra. En PHP, el valor false se considera falso en el contexto de una condición, 
                mientras que un número (como la posición devuelta por strpos()) se considera verdadero.
            */

            /*
                En este metodo $request->name es el nombre que viene de dropzone, por ejemplo upload_qr_plin
            */

            if (strpos($option->name, $request->name) !== false) {
                //La subcadena 'upload' fue encontrada en la posición $posicion ($position es lo que devuelve strpos)
                $option->delete(); //procedemos a borrarla porque la reemplazaremos por la nueva
            } else {
                //La subcadena 'upload' no fue encontrada , por lo que procedemos a borrarla

            }
        }

        //Lueho de borrado todo subimos e insertamos el registro creado

        $image = Storage::url(ImageModel::DIR_ROOT . "/" .  $this->uploadImage($request, "options", 0, true));

        $store->options()->create(
            [
                'name' => $request->name,
                'value' => $image,
            ],
        );

        return $image;
    }

    function uploadImage($request, $dir = "", $size = 0, $link = false)
    {
        $originalName = $request->file('file')->getClientOriginalName();

        // Extraemos la extensión en minúsculas
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Generamos un nombre único
        $name = md5(time() . $originalName . $dir . Str::random(10)) . '.' . $extension;

        // Carpeta relativa dentro del storage público
        $folder = ImageModel::DIR_ROOT . '/' . $dir;

        // Crear carpeta si no existe
        Storage::disk('public')->makeDirectory($folder, 0775, true);

        // Ruta absoluta para Intervention
        $file_path = Storage::disk('public')->path($folder . '/' . $name);

        // Crear instancia de la imagen
        $image = Image::read($request->file('file'));


        // Verificar y corregir orientación EXIF
        $exifData = $image->exif();
        if ($exifData && $exifData->has('Orientation')) {
            switch ($exifData->has('Orientation')) {
                case 3:
                    $image->rotate(180);
                    break;
                case 6:
                    $image->rotate(-90); // 90° antihorario
                    break;
                case 8:
                    $image->rotate(90); // 90° horario
                    break;
            }
        }

        // Redimensionar solo si `$size > 0`
        if ($size > 0) {
            $width = $size;
            $height = intval($image->height() * ($width / $image->width()));

            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Guardar imagen sin pérdida de calidad
        if (in_array($extension, ['jpg', 'jpeg', 'webp'])) {
            $image->save($file_path, 100); // Calidad máxima para JPG y WebP
        } else {
            $image->save($file_path); // PNG y otros formatos no pierden calidad
        }

        return $dir . '/' . $name;
    }

    function uploadImageToS3($request, $dir = "", $size = 0, $link = false)

    {

        Log::info('usando el Spaces S3');

        $originalName = $request->file('file')->getClientOriginalName();

        //extraemos la extencio
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));

        //Creando un nombre
        $name = md5(time() . $originalName . $dir . Str::random(10)) . '.' . $extension;

        //Estableciendo el directorio donde se guardara la imagen

        $path = Storage::path($dir);

        //sino existe el directorio entonces lo creamos
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        //establecemos la ruta donde se guardara el archivo
        $file_path = Storage::path($dir . '/' . $name);

        $image = Image::read($request->file('file')); //OJO Image::make es de intervention no es del Model Imagen

        //Comprobamos si la foto tiene exif

        $exifData = $image->exif();

        if ($exifData && isset($exifData['Orientation'])) {

            $orientation = $exifData['Orientation'];

            // Valores posibles para la orientación:
            // 1: Sin rotación
            // 3: Rotación 180 grados
            // 6: Rotación 90 grados en sentido horario
            // 8: Rotación 90 grados en sentido antihorario
            // ...

            // if ($orientation === 6 || $orientation === 8) {
            //     // La imagen está rotada
            //     // Puedes realizar acciones específicas aquí
            //     $image->rotate(-90);

            // } 

            switch ($orientation) {

                case '3':
                    $image->rotate(180);
                    break;

                case '6':
                    $image->rotate(-90);

                    break;

                case '8':
                    $image->rotate(90);
                    break;

                default:
                    # code...
                    break;
            }
        }

        if ($size > 0) {

            //Creamos la imagen segun el tamano deseado
            $image->resize($size, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            $image->save($file_path);

            Storage::disk('b2')->put($dir . '/' . $name, file_get_contents($file_path), 'public');

            return $dir . '/' . $name;
        } else {
            //Creamos la imagen original
            $name = Storage::disk('b2')->putFile($dir, $request->file('file'), 'public');

            return $name;
        }

        // $name = Storage::disk('spaces')->put($dir, file_get_contents($file_path), 'public');

        Log::info('La imagen guardada en S3 es: ' . $name . ' en spaces');
    }

    function getArrayUpload($request, $server_save = "local")
    {

        Log::info($request);

        try {

            $request->validate([
                'file' => 'required|image|max:20480'  //10 megas
            ]);

            // $url = Storage::put('products', $request->file('file'));

            if ($server_save == "local") {
                // $name = $this->uploadImage($request, $request['dir']); //Imagen original
                $thumb =  $this->uploadImage($request, $request['dir'], 360);
                $medium = $this->uploadImage($request, $request['dir'], 750);
                $large = $this->uploadImage($request, $request['dir']); //La imagen original

                $data = array(
                    'name' => $request->file('file')->getClientOriginalName(),
                    'thumbnail' => $thumb,
                    'medium' => $medium,
                    'large' => $large,
                    'usage' => $request['usage'],
                );
            } else {

                if ($server_save == "S3") {

                    $thumb = $this->uploadImageToS3($request, $request['dir'], 360);
                    Storage::delete($thumb);
                    $medium = $this->uploadImageToS3($request, $request['dir'], 750);
                    Storage::delete($medium);
                    $large = $this->uploadImageToS3($request, $request['dir']);
                    Storage::delete($large);

                    $data = array(
                        'name' => $request->file('file')->getClientOriginalName(),
                        'thumbnail' => $thumb,
                        'medium' => $medium,
                        'large' => $large,
                    );
                }
            }

            // Log::info($data);

            return $data;
        } catch (\Throwable $th) {
            Log::info('no se paso la validacion de uploadImagesTrait o ha habido un problema al subir el archivos en uploadImagesTrait.php');
            Log::info($th);
            // Log::info($data);
            return false;
        }
    }

    /*
    array (
        'FileName' => 'php6DDB.tmp',
        'FileDateTime' => 1739294782,
        'FileSize' => 9594996,
        'FileType' => 2,
        'MimeType' => 'image/jpeg',
        'SectionsFound' => 'ANY_TAG, IFD0, THUMBNAIL, EXIF, GPS, INTEROP',
        'COMPUTED' => 
        array (
          'html' => 'width="4496" height="3000"',
          'Height' => 3000,
          'Width' => 4496,
          'IsColor' => 1,
          'ByteOrderMotorola' => 0,
          'ApertureFNumber' => 'f/8.0',
          'UserComment' => NULL,
          'UserCommentEncoding' => 'ASCII',
          'Thumbnail.FileType' => 2,
          'Thumbnail.MimeType' => 'image/jpeg',
        ),
        'Make' => 'NIKON CORPORATION',
        'Model' => 'NIKON D7200',
        'Orientation' => 8,
        'XResolution' => '300/1',
        'YResolution' => '300/1',
        'ResolutionUnit' => 2,
        'Software' => 'Ver.1.01 ',
        'DateTime' => '2023:10:08 10:45:09',
        'Artist' => NULL,
        'YCbCrPositioning' => 2,
        'Copyright' => NULL,
        'Exif_IFD_Pointer' => 348,
        'GPS_IFD_Pointer' => 18152,
        'THUMBNAIL' => 
        array (
          'Compression' => 6,
          'XResolution' => '300/1',
          'YResolution' => '300/1',
          'ResolutionUnit' => 2,
          'JPEGInterchangeFormat' => 18280,
          'JPEGInterchangeFormatLength' => 10204,
          'YCbCrPositioning' => 2,
        ),
        'ExposureTime' => '10/5000',
        'FNumber' => '80/10',
        'ExposureProgram' => 0,
        'ISOSpeedRatings' => 560,
        'UndefinedTag:0x8830' => 2,
        'ExifVersion' => '0230',
        'DateTimeOriginal' => '2023:10:08 10:45:09',
        'DateTimeDigitized' => '2023:10:08 10:45:09',
        'ComponentsConfiguration' => '' . "\0" . '',
        'CompressedBitsPerPixel' => '4/1',
        'ExposureBiasValue' => '0/6',
        'MaxApertureValue' => '46/10',
        'MeteringMode' => 5,
        'LightSource' => 0,
        'Flash' => 16,
        'FocalLength' => '620/10',
        'MakerNote' => 'Nikon',
        'UserComment' => 'ASCII' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '' . "\0" . '',
        'SubSecTime' => '17',
        'SubSecTimeOriginal' => '17',
        'SubSecTimeDigitized' => '17',
        'FlashPixVersion' => '0100',
        'ColorSpace' => 1,
        'ExifImageWidth' => 4496,
        'ExifImageLength' => 3000,
        'InteroperabilityOffset' => 18120,
        'SensingMethod' => 2,
        'FileSource' => '',
        'SceneType' => '',
        'CFAPattern' => '' . "\0" . '' . "\0" . '' . "\0" . '',
        'CustomRendered' => 0,
        'ExposureMode' => 0,
        'WhiteBalance' => 0,
        'DigitalZoomRatio' => '1/1',
        'FocalLengthIn35mmFilm' => 93,
        'SceneCaptureType' => 0,
        'GainControl' => 1,
        'Contrast' => 0,
        'Saturation' => 0,
        'Sharpness' => 0,
        'SubjectDistanceRange' => 0,
        'GPSVersion' => '' . "\0" . '' . "\0" . '',
        'InterOperabilityIndex' => 'R98',
        'InterOperabilityVersion' => '0100',
      )  
    */

    function upload_to_thumb_medium_large($request, $dir, $server_save = "local")
    {


        $image = Image::read($request->file('file')); //OJO Image::make es de intervention no es del Model Imagen

        //Comprobamos si la foto tiene exif

        $exifData = $image->exif();

        Log::info("imprimiendo exif");
        Log::info($exifData);

        Log::info($request);

        try {

            $request->validate([
                'file' => 'required|image|max:20480'  //20 megas
            ]);

            // $url = Storage::put('products', $request->file('file'));

            if ($server_save == "local") {

                // $name = $this->uploadImage($request, $request['dir']); //Imagen original
                $thumb =  $this->uploadImage($request, $dir, 360);
                $medium = $this->uploadImage($request, $dir, 750);
                $large = $this->uploadImage($request, $dir); //La imagen original

                $data = array(
                    'name' => $request->file('file')->getClientOriginalName(),
                    'thumbnail' => $thumb,
                    'medium' => $medium,
                    'large' => $large,
                    'usage' => $request['usage'],
                    'brand' => $exifData['Make'],
                    'model' => $exifData['Model'],
                    'exif' => $exifData,
                    'capture_date' => $exifData['DateTime'],
                );
            } else {

                if ($server_save == "S3") {

                    $thumb = $this->uploadImageToS3($request, $dir, 360);
                    Storage::delete($thumb);
                    $medium = $this->uploadImageToS3($request, $dir, 750);
                    Storage::delete($medium);
                    $large = $this->uploadImageToS3($request, $dir);
                    Storage::delete($large);

                    $data = array(
                        'name' => $request->file('file')->getClientOriginalName(),
                        'thumbnail' => $thumb,
                        'medium' => $medium,
                        'large' => $large,
                        'usage' => $request['usage'],
                        'brand' => $exifData['Make'],
                        'model' => $exifData['Model'],
                        'exif' => $exifData,
                        'capture_date' => $exifData['DateTime'],
                    );
                }
            }

            // Log::info($data);

            return $data;
        } catch (\Throwable $th) {
            Log::info('no se paso la validacion de uploadImagesTrait o ha habido un problema al subir el archivos en uploadImagesTrait.php');
            Log::info($th);
            // Log::info($data);
            return false;
        }
    }
}
