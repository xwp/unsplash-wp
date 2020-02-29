#!/bin/bash

source ./bin/includes.sh

printf "Shutting down containers ... "

docker-compose down 2>/dev/null

printf "$(action_format "done")"
echo ""

# From: http://patorjk.com/software/taag/#p=display&c=echo&f=Standard&t=Unsplash
echo "  _   _                 _           _     ";
echo " | | | |_ __  ___ _ __ | | __ _ ___| |__  ";
echo " | | | | '_ \/ __| '_ \| |/ _\` / __| '_ \ ";
echo " | |_| | | | \__ \ |_) | | (_| \__ \ | | |";
echo "  \___/|_| |_|___/ .__/|_|\__,_|___/_| |_|";
echo "                 |_|                      ";

echo "See you again soon, same bat time, same bat channel?"
echo ""
