algoritmo "ATIVIDADE 13"
var
   i, qtd, total, maiorVenda, diaMaior: inteiro
inicio
   total <- 0
   maiorVenda <- 0
   diaMaior <- 0

   para i de 1 ate 30 faca
      escreva("Venda dia ", i, ": ")
      leia(qtd)

      total <- total + qtd

      se qtd < 5 entao
         escreval("ALERTA: Estoque Baixo!")
      fimse

      se qtd > maiorVenda entao
         maiorVenda <- qtd
         diaMaior <- i
      fimse
   fimpara

   escreval("Total vendido: ", total)
   escreval("Dia maior venda: ", diaMaior, " (", maiorVenda, " un.)")
fimalgoritmo