<?php

namespace Pdaether\BlockWire\Parsers;

use DOMDocument;

class Editor extends Parser implements ParserInterface
{
    protected static array $editorCssCache = [];

    protected function resolveEditorCss(): string
    {
        $configCss = config('blockwire.preview_css');
        $path = $configCss && file_exists(public_path($configCss))
            ? public_path($configCss)
            : __DIR__.'/../../public/editor.css';

        $cacheKey = $path.'|'.(file_exists($path) ? filemtime($path) : 'missing');

        if (! array_key_exists($cacheKey, self::$editorCssCache)) {
            self::$editorCssCache[$cacheKey] = file_exists($path)
                ? file_get_contents($path)
                : '';
        }

        return self::$editorCssCache[$cacheKey];
    }

    public function output(): string
    {
        if ($this->context !== 'editor') {
            return $this->input;
        }

        $dom = new DOMDocument;

        $internalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML($this->input);

        libxml_use_internal_errors($internalErrors);

        $editorCss = $this->resolveEditorCss();

        $activeBorderCss = '
            [drag-item]::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                border: 2px solid #9ca3af;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease, border-color 0.2s ease;
            }
            [drag-item]:hover::before {
                opacity: 1;
            }
            [drag-item].active::before {
                opacity: 1 !important;
                border-color: var(--active-border-color) !important;
            }
            [drag-item].active:hover::before {
                opacity: 1 !important;
                border-color: var(--active-border-color) !important;
            }
            [drag-item][data-show="0"]::before {
                opacity: 1 !important;
                border-color: #fca5a5 !important;
            }
            [drag-item][data-show="0"].active::before {
                opacity: 1 !important;
                border-color: #991b1b !important;
            }
            [drag-item][data-show="0"].active:hover::before {
                opacity: 1 !important;
                border-color: #991b1b !important;
            }

            [drag-item] .block-label {
                position: absolute;
                top: 0;
                left: 0;
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
                background-color: #9ca3af;
                color: white;
                border-bottom-right-radius: 0.375rem;
                opacity: 0;
                transition: opacity 0.2s ease;
                z-index: 10;
                pointer-events: none;
            }

            [drag-item]:hover .block-label,
            [drag-item].active .block-label {
                opacity: 1;
            }

            [drag-item].active .block-label {
                background-color: var(--active-border-color);
            }

            [drag-item][data-show="0"].active .block-label {
                background-color: #991b1b;
            }

            [drag-item] .block-actions {
                position: absolute;
                top: 0;
                right: 0;
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
                background-color: #9ca3af;
                color: white;
                border-bottom-left-radius: 0.375rem;
                opacity: 0;
                transition: opacity 0.2s ease;
                z-index: 10;
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }

            [drag-item]:hover .block-actions,
            [drag-item].active .block-actions {
                opacity: 1;
            }

            [drag-item].active .block-actions {
                background-color: var(--active-border-color);
            }

            [drag-item][data-show="0"].active .block-actions {
                background-color: #991b1b;
            }

            [drag-item] .block-actions button {
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                background: transparent;
                border: none;
                cursor: pointer;
                padding: 0;
                margin: 0;
                pointer-events: auto;
            }

            [drag-item] .block-actions button:hover {
                opacity: 0.8;
            }

            [drag-item] {
                transform-origin: center;
            }

            [drag-item]::after {
                content: "";
                transition: opacity 0.15s ease, height 0.15s ease;
            }

            [drop-placeholder] {
                transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
            }

            [drag-item].blockwire-entering {
                animation: blockwire-enter 280ms cubic-bezier(0.22, 1, 0.36, 1);
            }

            [drag-item].blockwire-removing {
                animation: blockwire-remove 180ms ease-in forwards;
                pointer-events: none;
            }

            @keyframes blockwire-enter {
                from {
                    opacity: 0;
                    transform: translateY(10px) scale(0.97);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            @keyframes blockwire-remove {
                from {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
                to {
                    opacity: 0;
                    transform: translateY(-6px) scale(0.97);
                }
            }
        ';

        $styleElement = $dom->createElement('style', htmlentities($editorCss.$activeBorderCss));
        $styleElement->setAttribute('type', 'text/css');

        $head = $dom->getElementsByTagName('head')->item(0);

        if ($head) {
            $head->appendChild($styleElement);
        } else {
            $dom->appendChild($styleElement);
        }

        return $dom->saveHTML();
    }
}
