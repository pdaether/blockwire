<?php

namespace Pdaether\BlockWire\Parsers;

use DOMDocument;

class Editor extends Parser implements ParserInterface
{
    public function output()
    {
        if ($this->context !== 'editor') {
            return $this->input;
        }

        $dom = new DOMDocument;

        $internalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML($this->input);

        libxml_use_internal_errors($internalErrors);

        $configCss = config('blockwire.preview_css');

        if ($configCss && file_exists(public_path($configCss))) {
            $editorCss = file_get_contents(public_path($configCss));
        } else {
            $editorCss = file_get_contents(__DIR__.'/../../public/editor.css');
        }

        $activeBorderCss = '
            [drag-item].active::before {
                border-color: var(--active-border-color) !important;
            }
            [drag-item].active:hover::before {
                border-color: var(--active-border-color) !important;
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
