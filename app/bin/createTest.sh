#!/bin/sh

OPTIND=1

#REPO_NAME
#ISSUE_NUMBER
#BRANCH_NAME

while getopts "h?r:i:b:" opt; do
    case "$opt" in
    h|\?)
        echo "-r <název repozitáře> -i <číslo issue> -b <název větve>"
        exit 0
        ;;
    r)  REPO_NAME=$OPTARG
        ;;
    i)  ISSUE_NUMBER=$OPTARG
        ;;
	b)  BRANCH_NUMBER=$OPTARG
        ;;
    esac
done

shift $((OPTIND-1))

[ "$1" = "--" ] && shift

printf "Připravím document_root: "
cp -R /var/www/$REPO_NAME/staging /var/www/${REPO_NAME}/test${ISSUE_NUMBER}
cd /var/www/${REPO_NAME}/test${ISSUE_NUMBER}
git clean -xdf temp/ log/
chmod -R 0777 temp/ log/
printf "OK\n"

printf "Přepnu větev:\n"
git fetch --prune
git checkout ${BRANCH_NAME}

printf "Připravím build:\n"
make build-staging

printf "\nHotovo\n"
