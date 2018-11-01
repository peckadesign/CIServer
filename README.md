# CI Server

## Instalace

Je nutné vytvořit `config.local.neon` podle obsahu vzorového `config.local.example.neon`


## Automatické vytváření testovacích prostředí

Při vytvoření pull requestu na GitHubu dojde k automatickému vytvoření testovacího serveru. URL je http://<název repozitáře>.test<číslo pull requestu>.peckadesign.com. Pro správnou funkčnost musí být stagingové prostředí nainstalováno v adresáři `/var/www/<název repozitáře>/staging`. 

Do nově vytvořeného testovacího prostředí je překopírován soubor `/var/www/<název repozitáře>/local.neon`. Formát není specifikován, jen výskyty `testX` jsou nahrazeny za `test<číslo pull requestu>` (pokud pull request obsahuje databázové migrace), případně za staging. Testovací databáze se sama nevytváří, programátor ji musí vytvořit sám během vývoje (což je žádnoucí, aby nerozbil staging).

```neon
parameters:
	database:
		database: testX # testX je nahrazeno test<číslo pull requestu>
```


## Automatická aktualizace testovacích prostředí

Při aktualizaci větve dojde k aktualizaci testovacího prostředí, na kterém je přepnutá aktualizovaná větev. Zároveň dojde k novému sestavení. S každou aktualizací jsou tak testovací prostředí připravená s aktuálními změnami.

Konkrétně se spouští tyto úlohy:

1. Dojde k aktualizi upstreamů všech testovacích prostředí změněného repozitáře. Jsou pročištěny smazané větve.
2. Dojde k vyresetování testovacího prostředí, na kterém je přepnutá aktualizovaná větev, na aktuální verzi.
3. Pokud je k dispozici Makefile, jsou spuštěny dva cíle: `make clean-cache` a `make build-staging`. Pokud Makefile není přítomen, je promazán adresář `temp/cache`, případně `temp`


## Vlastní databáze pro testovací prostředí

Pokud PR obsahuje DB migrace, je v `local.neon` připraven název databáze pro vytvořený PR. Ve vzorovém názvu v `local.neon` je `testX` nahrazeno za `testČísloPR`. Stejný formát je uvedený i v `dbname.conf`. Při zavření PR je podle tohoto formátu nalezena případná databáze a smazána.


## Vlastní redis namespace pro testovací prostředí

Aby se správně nastavil vlastní redis namespace pro testovací server je potřeba mít v `local.neon` nakonfigurovaný redis.
Stejně tak jako s `testX` pro databázi je nutné specifikovat pro redis `redisX`. 
Database se specifikuje aby se redis dokázal zpětně uklidit a promazat všechny klíče vytvořené v namespacu testovacího serveru.
```neon
redis:
	database: 3
	cacheKey: redisX
```
