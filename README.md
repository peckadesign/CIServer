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
