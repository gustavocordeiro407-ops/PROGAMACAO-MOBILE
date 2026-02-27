algoritmo "ATIVIDADE 24"
var n, soma, qtd: real
inicio
   soma <- 0
   qtd <- 0
   escreva("Digite um nº (0 para sair): ")
   leia(n)
   enquanto n <> 0 faca
      soma <- soma + n
      qtd <- qtd + 1
      escreva("Digite outro nº: ")
      leia(n)
   fimenquanto
   escreval("Qtd digitada: ", qtd)
   escreval("Soma total: ", soma)
fimalgoritmo