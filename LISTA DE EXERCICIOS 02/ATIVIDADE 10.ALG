algoritmo "ATIVIDADE 11"
var
   valor, totalBruto, totalDesc, desc: real
inicio
   totalBruto <- 0
   totalDesc <- 0

   escreva("Valor compra (0 sai): ")
   leia(valor)

   enquanto valor <> 0 faca
      desc <- 0
      se valor > 100 entao
         desc <- valor * 0.05
      fimse

      totalBruto <- totalBruto + valor
      totalDesc <- totalDesc + desc

      escreva("Valor compra (0 sai): ")
      leia(valor)
   fimenquanto

   escreval("Faturamento Bruto: ", totalBruto)
   escreval("Total Descontos: ", totalDesc)
   escreval("Faturamento Líquido: ", totalBruto - totalDesc)
fimalgoritmo