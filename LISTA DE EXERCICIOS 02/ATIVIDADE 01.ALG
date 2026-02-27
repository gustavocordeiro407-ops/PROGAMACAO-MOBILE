algoritmo "ATIVIDADE 01"
var
   i, n, somaPares, qtdImpares: inteiro
inicio
   somaPares <- 0
   qtdImpares <- 0
   para i de 1 ate 20 faca
      escreva("Digite o ", i, "º número: ")
      leia(n)
      se n % 2 = 0 entao
         somaPares <- somaPares + n
      senao
         qtdImpares <- qtdImpares + 1
      fimse
   fimpara
   escreval("Soma dos pares: ", somaPares)
   escreval("Quantidade de ímpares: ", qtdImpares)
fimalgoritmo