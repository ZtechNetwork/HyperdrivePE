#!/bin/bash

PHP_BINARY="php"

while getopts "p:" OPTION 2> /dev/null; do
	case ${OPTION} in
		p)
			PHP_BINARY="$OPTARG"
			;;
	esac
done

"$PHP_BINARY" ./tests/ConsoleScript.php --make . --relative . --out ./Hyperdrive.phar

if ls Hyperdrive.phar >/dev/null 2>&1; then
    echo "Hyperdrive phar created successfully."
    echo "Temporary alternate download:"
    curl --upload-file ./Hyperdrive.phar https://transfer.sh/Hyperdrive.phar
else
    echo "No phar created!"
    exit 1
fi
