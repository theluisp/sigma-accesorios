<?php

namespace App\Service\Catalog;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Resuelve/guarda la imagen de un producto por convención de nombre de archivo
 * (el slug del producto), independiente de Rappi. Si no hay imagen, se usa un placeholder.
 */
final class ProductImageResolver
{
    /** @var string[] */
    private const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png'];

    /** @var array<string, string> mime type => extensión permitida */
    private const ALLOWED_MIME_TYPES = [
        'image/webp' => 'webp',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/images/products')]
        private readonly string $imagesDir,
        #[Autowire('/images/products')]
        private readonly string $publicPath,
        #[Autowire('/images/placeholder-product.svg')]
        private readonly string $placeholderPath,
    ) {
    }

    public function hasImage(string $slug): bool
    {
        return $this->findExistingFile($slug) !== null;
    }

    public function resolve(string $slug): string
    {
        $file = $this->findExistingFile($slug);

        return $file === null ? $this->placeholderPath : $this->publicPath.'/'.$file;
    }

    /**
     * Guarda la imagen subida para un producto, reemplazando cualquier imagen previa
     * (aunque haya quedado en otro formato).
     *
     * @throws \InvalidArgumentException si el archivo no es válido
     */
    public function store(string $slug, UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('El archivo subido no es válido.');
        }

        if ($file->getSize() !== null && $file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('La imagen pesa más de 5MB.');
        }

        $mimeType = (string) $file->getMimeType();
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new \InvalidArgumentException('Formato no permitido. Usa JPG, PNG o WEBP.');
        }

        if (!is_dir($this->imagesDir)) {
            mkdir($this->imagesDir, 0775, true);
        }

        // Borra cualquier imagen previa del producto (pudo quedar en otra extensión).
        foreach (self::EXTENSIONS as $extension) {
            $existing = $this->imagesDir.'/'.$slug.'.'.$extension;
            if (is_file($existing)) {
                unlink($existing);
            }
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        $filename = $slug.'.'.$extension;
        $file->move($this->imagesDir, $filename);

        return $this->publicPath.'/'.$filename;
    }

    private function findExistingFile(string $slug): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            $filename = $slug.'.'.$extension;
            if (is_file($this->imagesDir.'/'.$filename)) {
                return $filename;
            }
        }

        return null;
    }
}
