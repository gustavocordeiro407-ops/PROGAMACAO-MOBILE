algoritmo "ATIVIDADE 08"
var
   i: inteiro
   venda, bonus, totalBonus, maiorVenda: real
inicio
   totalBonus <- 0
   maiorVenda <- 0

   para i de 1 ate 10 faca
      escreva("Venda vendedor ", i, ": ")
      leia(venda)

      se venda > maiorVenda entao
         maiorVenda <- venda
      fimse

      se venda >= 5000 entao
         bonus <- venda * 0.10
      senao
         bonus <- venda * 0.05
      fimse
      totalBonus <- totalBonus + bonus
   fimpara

   escreval("Total pago em bônus: ", totalBonus)
   escreval("Maior venda: ", maiorVenda)
fimalgoritmo