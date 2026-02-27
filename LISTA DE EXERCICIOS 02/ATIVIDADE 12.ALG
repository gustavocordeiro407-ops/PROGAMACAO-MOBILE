algoritmo "ATIVIDADE 12"
var
   idade, soma, qtd, maiores18, menores: real
inicio
   soma <- 0
   qtd <- 0
   maiores18 <- 0
   menores <- 0

   escreva("Idade (0 sai): ")
   leia(idade)

   enquanto idade <> 0 faca
      soma <- soma + idade
      qtd <- qtd + 1

      se idade >= 18 entao
         maiores18 <- maiores18 + 1
      senao
         menores <- menores + 1
      fimse

      escreva("Idade (0 sai): ")
      leia(idade)
   fimenquanto

   se qtd > 0 entao
      escreval("Média idades: ", soma / qtd)
      escreval("Maiores de 18: ", maiores18)
      escreval("% Menores: ", (menores / qtd) * 100, "%")
   fimse
fimalgoritmo