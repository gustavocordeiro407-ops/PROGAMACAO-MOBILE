algoritmo "ATIVIDADE 16"
var
   valor, taxa, final: real
   meses: inteiro
inicio
   escreva("Valor Empréstimo: ")
   leia(valor)
   escreva("Taxa mensal (ex: 0.02 para 2%): ")
   leia(taxa)
   escreva("Meses: ")
   leia(meses)

   final <- valor * ((1 + taxa) ^ meses)

   escreval("Valor Final: ", final:4:2)
   escreval("Total Juros: ", final - valor:4:2)
fimalgoritmo