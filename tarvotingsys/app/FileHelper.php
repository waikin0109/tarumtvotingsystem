<?php
// (no namespace — keep it global if you like, or add one and update the `use` line)

class FileHelper
{
    private array $allowedPaths = [];
    private string $permission;
    /** Base dir where this file lives (usually .../app) */
    private string $base;

    public function __construct(string $permission)
    {
        $this->permission = $permission;

        // Anchor to the directory where FileHelper.php is located.
        // If FileHelper.php is in /app, then $this->base === .../app/
        $this->base = rtrim(realpath(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->initializePaths();
    }

    /** Join relative paths to $this->base and normalize separators */
    private function prefixWithBase(array $paths): array
    {
        foreach ($paths as $key => $relPath) {
            // Normalize incoming slashes and strip any leading slash
            $rel = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
            $paths[$key] = $this->base . $rel;
        }
        return $paths;
    }

    private function initializePaths(): void
    {
        // IMPORTANT: make this relative to /app (where FileHelper.php sits)
        // So View/VotingView/election-event.php resolves to .../app/View/VotingView/election-event.php
        $electionEventPaths = $this->prefixWithBase([
            'ElectionEventList' => 'View/VotingView/electionEvent.php',
        ]);

        // If your CSS is under /public/css, and FileHelper sits in /app,
        // then go one level up to project root and into public:
        $assetPaths = $this->prefixWithBase([
            'AppCSS' => '../public/css/app.css',
        ]);

        switch ($this->permission) {
            case 'election_event':
                $this->allowedPaths = array_merge($electionEventPaths, $assetPaths);
                break;
            case 'asset':
                $this->allowedPaths = $assetPaths;
                break;
            default:
                $this->allowedPaths = [];
        }
    }

    public function getFilePath(string $key): ?string
    {
        return $this->allowedPaths[$key] ?? null;
    }
}
