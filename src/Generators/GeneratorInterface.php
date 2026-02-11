<?php

namespace TheDevBacklog\Generators;

interface GeneratorInterface
{
    /**
     * Generate the file content based on the configuration
     *
     * @param array $config
     * @return string
     */
    public function generate(array $config): string;

    /**
     * Get the output filename
     *
     * @param array $config
     * @return string
     */
    public function getFilename(array $config): string;
}
