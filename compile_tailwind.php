<?php
function compile_tailwind(string $allContent = "")
{
    $contentFileHandle = tmpfile();
    $configFileHandle = tmpfile();

    try {
        $contentFileName = stream_get_meta_data($contentFileHandle)['uri'];
        $configFileName = stream_get_meta_data($configFileHandle)['uri'];

        fwrite($contentFileHandle, $allContent);
        fseek($contentFileHandle, 0);

        fwrite($configFileHandle, <<<EOT
            module.exports = {
              content: ['{$contentFileName}'],
              important: 'body',
              plugins: [],
            }
            EOT);
        fseek($configFileHandle, 0);

        $descriptorSpec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];

        $process = proc_open(
            "npx tailwindcss --input - --config {$configFileName}",
            $descriptorSpec,
            $pipes,
            __DIR__,
            ['PATH' => '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/run/current-system/sw/bin/']
        );

        if (is_resource($process)) {
            fwrite($pipes[0], '@tailwind components; @tailwind utilities');
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $ret = proc_close($process);

            if ($ret === 0) {
                return $output;
            } else {
                throw new \InvalidArgumentException("CompileTailwind failed: " . $error . PHP_EOL);
            }
        } else {
            throw new \InvalidArgumentException('CompileTailwind failed.' . PHP_EOL);
        }
    } catch (\Throwable $th) {
        echo $th;
        throw $th;
    } finally {
        fclose($contentFileHandle);
        fclose($configFileHandle);
    }
}

$l_sHtml = '<h1 class="text-3xl font-bold underline text-orange-500">Hello world!</h1>';

?>
<!doctype html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        <?php echo compile_tailwind($l_sHtml); ?>
    </style>
</head>

<body>
    <?php echo $l_sHtml; ?>
</body>

</html>
